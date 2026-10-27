<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Associate Cerdas Cermat discipline penalties with the round where they occurred. */
class Migration_Cerdas_cermat_penalty_round extends CI_Migration
{
	public function up()
	{
		if ($this->db->query("ALTER TABLE penalties ADD round_key VARCHAR(30) NULL AFTER points") === FALSE)
		{
			throw new RuntimeException('Migrasi babak penalti Cerdas Cermat gagal: '.$this->db->error()['message']);
		}
	}

	public function down()
	{
		if ($this->db->query('ALTER TABLE penalties DROP COLUMN round_key') === FALSE)
		{
			throw new RuntimeException('Rollback babak penalti Cerdas Cermat gagal: '.$this->db->error()['message']);
		}
	}
}
