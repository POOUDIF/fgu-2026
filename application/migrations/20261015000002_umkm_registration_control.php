<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Allow Super Admin to control public UMKM bazaar registration. */
class Migration_Umkm_registration_control extends CI_Migration
{
	public function up()
	{
		if ( ! $this->db->query('ALTER TABLE events ADD umkm_registration_open TINYINT(1) NOT NULL DEFAULT 0'))
		{
			throw new RuntimeException('Pengaturan buka/tutup Bazaar UMKM gagal ditambahkan: '.$this->db->error()['message']);
		}
	}

	public function down()
	{
		if ( ! $this->db->query('ALTER TABLE events DROP COLUMN umkm_registration_open'))
		{
			throw new RuntimeException('Pengaturan buka/tutup Bazaar UMKM gagal dihapus: '.$this->db->error()['message']);
		}
	}
}
