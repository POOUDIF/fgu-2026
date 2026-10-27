<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Keep Lomba Stand Bazaar from duplicating the special Bazaar Desa registration form. */
class Migration_Retire_duplicate_bazaar_competition extends CI_Migration
{
	public function up()
	{
		$this->run_ddl("UPDATE competitions
			SET is_active = 0
			WHERE slug = 'lomba-stand-bazaar'");
	}

	public function down()
	{
		$this->run_ddl("UPDATE competitions
			SET is_active = 1
			WHERE slug = 'lomba-stand-bazaar'");
	}

	private function run_ddl(string $sql): void
	{
		if ($this->db->query($sql) === FALSE)
		{
			$error = $this->db->error();
			throw new RuntimeException('Penyatuan pendaftaran Bazaar Desa gagal: '.($error['message'] ?? 'Database rejected a schema statement.'));
		}
	}
}
