<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Skema FGU 3.0 (MySQL 8 / MariaDB 10.5+, InnoDB, utf8mb4) — mengikuti alur prototype:
 *
 *   villages ─┬─ users (admin desa)
 *             ├─ participants ── entry_members ── entries ── competitions ── criteria
 *             └─ entries (pendaftaran per desa, atau kiriman publik)       └─ competition_judges
 *   entries ── scores (juri × kriteria) ── users(judge)
 *
 * Satu "entry" adalah satuan yang dinilai juri:
 *   - lomba perorangan : tiap peserta = 1 entry
 *   - lomba regu       : seluruh peserta satu desa = 1 entry
 *
 * Aturan yang ditegakkan database:
 *  - participants_one_per_village_name : satu orang (nama, per desa) hanya boleh di satu lomba
 *  - scores_one_per_criterion          : juri hanya sekali menilai tiap kriteria per entry
 *  - CHECK pada usia dan nilai >= 0
 * Aturan kontekstual (jadwal, kuota, nilai 50..maks, penugasan juri) ada di
 * application/libraries/*_service.php dan dijalankan di dalam transaksi.
 */
class Migration_Create_schema extends CI_Migration
{
	public function up()
	{
		$o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
		$sql = array();

		$sql[] = "CREATE TABLE villages (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			pic_name VARCHAR(100) NULL,
			sort_order SMALLINT NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY villages_name (name)
		) $o";

		$sql[] = "CREATE TABLE users (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			full_name VARCHAR(100) NOT NULL,
			email VARCHAR(190) NOT NULL,
			password_hash VARCHAR(255) NOT NULL,
			role ENUM('village_admin','judge','regional_admin','super_admin') NOT NULL,
			village_id INT UNSIGNED NULL,
			must_change_password TINYINT(1) NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			last_login_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY users_email (email),
			KEY users_role (role),
			CONSTRAINT users_village_fk FOREIGN KEY (village_id) REFERENCES villages (id),
			CONSTRAINT users_village_admin_has_village CHECK (role <> 'village_admin' OR village_id IS NOT NULL)
		) $o";

		$sql[] = "CREATE TABLE events (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(150) NOT NULL,
			event_date DATE NULL,
			venue VARCHAR(190) NULL,
			theme VARCHAR(190) NULL,
			registration_opens_at DATETIME NULL,
			registration_closes_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			CONSTRAINT events_window CHECK (registration_opens_at IS NULL OR registration_closes_at IS NULL OR registration_opens_at < registration_closes_at)
		) $o";

		$sql[] = "CREATE TABLE site_content (
			content_key VARCHAR(50) NOT NULL,
			content_value TEXT NOT NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (content_key)
		) $o";

		$sql[] = "CREATE TABLE competitions (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(100) NOT NULL,
			name VARCHAR(150) NOT NULL,
			cluster VARCHAR(100) NULL,
			age_label VARCHAR(150) NULL,
			type_label VARCHAR(100) NULL,
			participation_type ENUM('individual','team') NOT NULL DEFAULT 'individual',
			quota SMALLINT UNSIGNED NULL,
			submission_mode ENUM('offline','online') NOT NULL DEFAULT 'offline',
			place VARCHAR(190) NULL,
			icon VARCHAR(100) NULL,
			rules JSON NOT NULL,
			winner_count TINYINT UNSIGNED NOT NULL DEFAULT 3,
			sort_order SMALLINT NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			UNIQUE KEY competitions_slug (slug),
			CONSTRAINT competitions_quota CHECK (quota IS NULL OR quota >= 1),
			CONSTRAINT competitions_winners CHECK (winner_count >= 1)
		) $o";

		$sql[] = "CREATE TABLE criteria (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			competition_id INT UNSIGNED NOT NULL,
			name VARCHAR(190) NOT NULL,
			max_score DECIMAL(8,2) NOT NULL DEFAULT 100,
			sort_order SMALLINT NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY criteria_competition (competition_id, sort_order),
			CONSTRAINT criteria_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
			CONSTRAINT criteria_max_positive CHECK (max_score > 0)
		) $o";

		$sql[] = "CREATE TABLE competition_judges (
			competition_id INT UNSIGNED NOT NULL,
			judge_id INT UNSIGNED NOT NULL,
			PRIMARY KEY (competition_id, judge_id),
			KEY competition_judges_judge (judge_id),
			CONSTRAINT cj_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
			CONSTRAINT cj_judge_fk FOREIGN KEY (judge_id) REFERENCES users (id) ON DELETE CASCADE
		) $o";

		// name_key = nama dinormalkan (huruf kecil, spasi dirapatkan) untuk aturan "satu orang satu lomba".
		$sql[] = "CREATE TABLE participants (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			village_id INT UNSIGNED NOT NULL,
			name VARCHAR(100) NOT NULL,
			name_key VARCHAR(100) NOT NULL,
			age TINYINT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY participants_one_per_village_name (village_id, name_key),
			CONSTRAINT participants_village_fk FOREIGN KEY (village_id) REFERENCES villages (id),
			CONSTRAINT participants_age CHECK (age IS NULL OR age BETWEEN 1 AND 100)
		) $o";

		$sql[] = "CREATE TABLE entries (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			competition_id INT UNSIGNED NOT NULL,
			village_id INT UNSIGNED NOT NULL,
			source ENUM('village','public') NOT NULL DEFAULT 'village',
			submission_url VARCHAR(500) NULL,
			status ENUM('registered','disqualified') NOT NULL DEFAULT 'registered',
			disqualified_reason VARCHAR(500) NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entries_competition (competition_id, village_id),
			KEY entries_village (village_id),
			CONSTRAINT entries_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id),
			CONSTRAINT entries_village_fk FOREIGN KEY (village_id) REFERENCES villages (id)
		) $o";

		$sql[] = "CREATE TABLE entry_members (
			entry_id INT UNSIGNED NOT NULL,
			participant_id INT UNSIGNED NOT NULL,
			PRIMARY KEY (entry_id, participant_id),
			UNIQUE KEY entry_members_one_entry (participant_id),
			CONSTRAINT em_entry_fk FOREIGN KEY (entry_id) REFERENCES entries (id) ON DELETE CASCADE,
			CONSTRAINT em_participant_fk FOREIGN KEY (participant_id) REFERENCES participants (id) ON DELETE CASCADE
		) $o";

		$sql[] = "CREATE TABLE scores (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			entry_id INT UNSIGNED NOT NULL,
			judge_id INT UNSIGNED NOT NULL,
			criterion_id INT UNSIGNED NOT NULL,
			score DECIMAL(8,2) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY scores_one_per_criterion (entry_id, judge_id, criterion_id),
			KEY scores_judge (judge_id),
			KEY scores_criterion (criterion_id),
			KEY scores_created (created_at),
			CONSTRAINT scores_entry_fk FOREIGN KEY (entry_id) REFERENCES entries (id) ON DELETE CASCADE,
			CONSTRAINT scores_judge_fk FOREIGN KEY (judge_id) REFERENCES users (id),
			CONSTRAINT scores_criterion_fk FOREIGN KEY (criterion_id) REFERENCES criteria (id),
			CONSTRAINT scores_non_negative CHECK (score >= 0)
		) $o";

		$sql[] = "CREATE TABLE penalties (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			entry_id INT UNSIGNED NOT NULL,
			points DECIMAL(8,2) NOT NULL,
			reason VARCHAR(500) NULL,
			created_by INT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY penalties_entry (entry_id),
			CONSTRAINT penalties_entry_fk FOREIGN KEY (entry_id) REFERENCES entries (id) ON DELETE CASCADE,
			CONSTRAINT penalties_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
			CONSTRAINT penalties_positive CHECK (points > 0)
		) $o";

		// Riwayat koreksi: snapshot teks agar tetap terbaca meski data asal dihapus.
		$sql[] = "CREATE TABLE score_audit_log (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			action ENUM('edit','reset_judge') NOT NULL,
			competition_id INT UNSIGNED NULL,
			competition_name VARCHAR(150) NOT NULL,
			village_name VARCHAR(100) NOT NULL,
			entry_label VARCHAR(255) NOT NULL,
			judge_name VARCHAR(100) NOT NULL,
			criterion_name VARCHAR(190) NULL,
			old_score DECIMAL(8,2) NULL,
			new_score DECIMAL(8,2) NULL,
			reason VARCHAR(500) NOT NULL,
			changed_by INT UNSIGNED NULL,
			changed_by_name VARCHAR(100) NOT NULL,
			PRIMARY KEY (id),
			KEY audit_competition (competition_id, changed_at),
			KEY audit_changed (changed_at)
		) $o";

		// Sesi CI3 (sess_driver = database)
		$sql[] = "CREATE TABLE ci_sessions (
			id VARCHAR(128) NOT NULL,
			ip_address VARCHAR(45) NOT NULL,
			timestamp INT UNSIGNED NOT NULL DEFAULT 0,
			data BLOB NOT NULL,
			PRIMARY KEY (id),
			KEY ci_sessions_timestamp (timestamp)
		) $o";

		// Pembatasan percobaan login
		$sql[] = "CREATE TABLE login_attempts (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			email VARCHAR(190) NOT NULL,
			ip_address VARCHAR(45) NOT NULL,
			attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY login_attempts_lookup (email, ip_address, attempted_at)
		) $o";

		foreach ($sql as $statement)
		{
			if ( ! $this->db->query($statement))
			{
				$err = $this->db->error();
				throw new RuntimeException('Migrasi gagal: '.$err['message']."\n".$statement);
			}
		}
	}

	public function down()
	{
		$this->db->query('SET FOREIGN_KEY_CHECKS = 0');
		foreach (array(
			'login_attempts', 'ci_sessions', 'score_audit_log', 'penalties', 'scores', 'entry_members',
			'entries', 'participants', 'competition_judges', 'criteria',
			'competitions', 'site_content', 'events', 'users', 'villages',
		) as $table)
		{
			$this->db->query('DROP TABLE IF EXISTS '.$table);
		}
		$this->db->query('SET FOREIGN_KEY_CHECKS = 1');
	}
}
