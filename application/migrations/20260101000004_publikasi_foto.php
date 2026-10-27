<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add result publication and private participant photo paths. */
class Migration_Publikasi_foto extends CI_Migration
{
	public function up()
	{
		$columns = array(
			'competitions' => array(
				'results_published' => 'ALTER TABLE competitions ADD results_published TINYINT(1) NOT NULL DEFAULT 0',
			),
			'participants' => array(
				'photo_path' => 'ALTER TABLE participants ADD photo_path VARCHAR(255) NULL',
			),
		);

		foreach ($columns as $table => $definitions)
		{
			$existing = array_column($this->db->field_data($table), 'name');
			foreach ($definitions as $name => $sql)
			{
				if ( ! in_array($name, $existing, TRUE) && ! $this->db->query($sql))
				{
					throw new RuntimeException('Migrasi publikasi/foto gagal: '.$this->db->error()['message']);
				}
			}
		}
	}

	public function down()
	{
		$this->db->query('ALTER TABLE competitions DROP COLUMN results_published');
		$this->db->query('ALTER TABLE participants DROP COLUMN photo_path');
	}
}
