<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Absensi peserta lomba, rundown/denah publik, dan pendaftaran bazaar. */
class Migration_Attendance_rundown_bazaar extends CI_Migration
{
	public function up()
	{
		$this->run_ddl("ALTER TABLE entries
			ADD attendance_status ENUM('pending','present','absent') NOT NULL DEFAULT 'pending',
			ADD attendance_marked_at DATETIME NULL,
			ADD attendance_marked_by INT UNSIGNED NULL,
			ADD KEY entries_attendance (attendance_status),
			ADD CONSTRAINT entries_attendance_user_fk FOREIGN KEY (attendance_marked_by) REFERENCES users (id) ON DELETE SET NULL");

		$this->run_ddl("ALTER TABLE events
			ADD site_plan_path VARCHAR(255) NULL,
			ADD umkm_booth_quota SMALLINT UNSIGNED NOT NULL DEFAULT 1");

		$this->run_ddl("CREATE TABLE event_rundown (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			start_time VARCHAR(30) NOT NULL,
			detail TEXT NOT NULL,
			location VARCHAR(190) NOT NULL,
			sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY event_rundown_order (sort_order, id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->run_ddl("CREATE TABLE bazaar_umkm (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			owner_name VARCHAR(120) NOT NULL,
			brand_name VARCHAR(120) NOT NULL,
			village_id INT UNSIGNED NOT NULL,
			phone VARCHAR(30) NOT NULL,
			booth_number SMALLINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY bazaar_umkm_booth (booth_number),
			KEY bazaar_umkm_village (village_id),
			CONSTRAINT bazaar_umkm_village_fk FOREIGN KEY (village_id) REFERENCES villages (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->run_ddl("CREATE TABLE bazaar_village (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			village_id INT UNSIGNED NOT NULL,
			theme VARCHAR(150) NOT NULL,
			phone VARCHAR(30) NOT NULL,
			booth_number TINYINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY bazaar_village_unique_village (village_id),
			UNIQUE KEY bazaar_village_unique_booth (booth_number),
			CONSTRAINT bazaar_village_village_fk FOREIGN KEY (village_id) REFERENCES villages (id),
			CONSTRAINT bazaar_village_booth_check CHECK (booth_number BETWEEN 1 AND 9)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	public function down()
	{
		$this->run_ddl('DROP TABLE IF EXISTS bazaar_village');
		$this->run_ddl('DROP TABLE IF EXISTS bazaar_umkm');
		$this->run_ddl('DROP TABLE IF EXISTS event_rundown');
		$this->run_ddl('ALTER TABLE events DROP COLUMN umkm_booth_quota, DROP COLUMN site_plan_path');
		$this->run_ddl('ALTER TABLE entries DROP FOREIGN KEY entries_attendance_user_fk, DROP KEY entries_attendance, DROP COLUMN attendance_marked_by, DROP COLUMN attendance_marked_at, DROP COLUMN attendance_status');
	}

	private function run_ddl(string $sql): void
	{
		if ($this->db->query($sql) === FALSE)
		{
			$error = $this->db->error();
			throw new RuntimeException('Attendance/rundown/bazaar migration failed: '.($error['message'] ?? 'Database rejected a schema statement.'));
		}
	}
}
