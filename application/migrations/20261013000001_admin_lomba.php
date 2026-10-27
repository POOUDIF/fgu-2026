<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add the competition manager role and its competition-scoped assignments. */
class Migration_Admin_lomba extends CI_Migration
{
	public function up()
	{
		$this->run_ddl("ALTER TABLE users MODIFY role ENUM('village_admin','judge','competition_admin','regional_admin','super_admin') NOT NULL");
		$this->run_ddl("CREATE TABLE competition_managers (
			competition_id INT UNSIGNED NOT NULL,
			manager_id INT UNSIGNED NOT NULL,
			PRIMARY KEY (competition_id, manager_id),
			KEY competition_managers_manager (manager_id),
			CONSTRAINT cm_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
			CONSTRAINT cm_manager_fk FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		$this->run_ddl("ALTER TABLE score_audit_log MODIFY action ENUM('edit','reset_judge','manager_input','manager_edit') NOT NULL");
	}

	public function down()
	{
		$this->run_ddl('DROP TABLE IF EXISTS competition_managers');
		$this->run_ddl("ALTER TABLE users MODIFY role ENUM('village_admin','judge','regional_admin','super_admin') NOT NULL");
	}

	private function run_ddl(string $sql): void
	{
		if ($this->db->query($sql) === FALSE)
		{
			throw new RuntimeException('Migrasi Admin Lomba gagal: '.$this->db->error()['message']);
		}
	}
}
