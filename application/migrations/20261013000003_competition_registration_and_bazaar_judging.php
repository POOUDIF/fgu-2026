<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add per-competition registration switches and connect Bazaar Desa to judge scoring. */
class Migration_Competition_registration_and_bazaar_judging extends CI_Migration
{
	public function up()
	{
		$this->run_ddl("ALTER TABLE competitions
			ADD registration_open TINYINT(1) NOT NULL DEFAULT 0");
		$this->run_ddl('UPDATE competitions SET registration_open = 1');
		$this->run_ddl('ALTER TABLE entries MODIFY team_name VARCHAR(150) NULL');

		$competition = $this->db->where('slug', 'bazaar-desa')->get('competitions')->row_array();
		if ( ! $competition)
		{
			$this->run_ddl("INSERT INTO competitions (
				slug, name, cluster, age_label, type_label, participation_type, registration_mode,
				allow_team, scoring_method, quota, submission_mode, place, icon, rules,
				winner_count, sort_order, is_active, results_published, registration_open
			) VALUES (
				'bazaar-desa', 'Bazaar Desa', 'Bazaar', 'Desa', 'Stand Bazaar Desa', 'team', 'participant',
				0, 'weighted', 1, 'offline', 'Area Bazaar', NULL,
				'[\"Setiap desa mengirim satu stand Bazaar Desa.\",\"Penilaian dilakukan oleh juri yang ditugaskan Super Admin.\"]',
				3, 999, 1, 0, 0
			)");
			$competitionId = (int) $this->db->insert_id();
		}
		else
		{
			$competitionId = (int) $competition['id'];
		}
		$this->run_ddl('UPDATE competitions SET registration_open = 0 WHERE id = '.$competitionId);

		if ($this->db->where('competition_id', $competitionId)->count_all_results('criteria') === 0)
		{
			$this->run_ddl("INSERT INTO criteria (competition_id, name, weight, max_score, sort_order)
				VALUES (".$competitionId.", 'Penilaian Bazaar Desa', 100, 100, 1)");
		}

		$this->run_ddl("ALTER TABLE bazaar_village
			ADD entry_id INT UNSIGNED NULL,
			ADD UNIQUE KEY bazaar_village_entry (entry_id),
			ADD CONSTRAINT bazaar_village_entry_fk FOREIGN KEY (entry_id) REFERENCES entries (id) ON DELETE RESTRICT");

		$this->run_ddl("INSERT INTO entries (
			competition_id, village_id, source, team_name, entry_type, status, attendance_status, created_at
		)
		SELECT ".$competitionId.", b.village_id, 'village', b.theme, 'team', 'registered', 'present', b.created_at
		FROM bazaar_village b");

		$this->run_ddl("UPDATE bazaar_village b
			JOIN entries e ON e.competition_id = ".$competitionId." AND e.village_id = b.village_id
			SET b.entry_id = e.id");
		$this->run_ddl('ALTER TABLE bazaar_village MODIFY entry_id INT UNSIGNED NOT NULL');
	}

	public function down()
	{
		$competition = $this->db->where('slug', 'bazaar-desa')->get('competitions')->row_array();
		if ($competition)
		{
			if ($this->db->count_all('bazaar_village') > 0)
			{
				throw new RuntimeException('Rollback tidak dapat dilakukan selama data pendaftaran Bazaar Desa masih tersimpan.');
			}
			$competitionId = (int) $competition['id'];
			$hasScores = $this->db->from('scores s')->join('entries e', 'e.id = s.entry_id')
				->where('e.competition_id', $competitionId)->count_all_results() > 0;
			$hasDrafts = $this->db->from('score_drafts d')->join('entries e', 'e.id = d.entry_id')
				->where('e.competition_id', $competitionId)->count_all_results() > 0;
			if ($hasScores || $hasDrafts)
			{
				throw new RuntimeException('Rollback tidak dapat dilakukan karena Bazaar Desa sudah memiliki nilai atau draft juri.');
			}
			$this->run_ddl('DELETE FROM entries WHERE competition_id = '.$competitionId);
			$this->run_ddl('DELETE FROM competitions WHERE id = '.$competitionId);
		}

		$this->run_ddl('ALTER TABLE bazaar_village DROP FOREIGN KEY bazaar_village_entry_fk, DROP KEY bazaar_village_entry, DROP COLUMN entry_id');
		$this->run_ddl('ALTER TABLE entries MODIFY team_name VARCHAR(100) NULL');
		$this->run_ddl('ALTER TABLE competitions DROP COLUMN registration_open');
	}

	private function run_ddl(string $sql): void
	{
		if ($this->db->query($sql) === FALSE)
		{
			$error = $this->db->error();
			throw new RuntimeException('Migrasi pendaftaran dan penilaian Bazaar Desa gagal: '.($error['message'] ?? 'Database rejected a schema statement.'));
		}
	}
}
