<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Configure the five-to-ten participant range for Praktek Sholat Berjamaah. */
class Migration_Competition_minimum_participants extends CI_Migration
{
	public function up()
	{
		if ( ! $this->db->query('ALTER TABLE competitions ADD minimum_participants SMALLINT UNSIGNED NULL AFTER quota'))
		{
			throw new RuntimeException('Kolom jumlah minimal peserta gagal ditambahkan: '.$this->db->error()['message']);
		}

		$this->update_sholat_rule(5, 'Regu 5–10 orang per desa (minimal 5 dan maksimal 10 peserta).');
	}

	public function down()
	{
		$this->update_sholat_rule(NULL, '10 orang/desa: 1 imam, 5 putra, 4 putri.');
		if ( ! $this->db->query('ALTER TABLE competitions DROP COLUMN minimum_participants'))
		{
			throw new RuntimeException('Kolom jumlah minimal peserta gagal dihapus: '.$this->db->error()['message']);
		}
	}

	private function update_sholat_rule(?int $minimum, string $rule): void
	{
		$query = $this->db->where('slug', 'praktek-sholat-berjamaah')->get('competitions');
		if ($query->num_rows() !== 1)
		{
			throw new RuntimeException('Data lomba Praktek Sholat Berjamaah harus cocok tepat satu kali.');
		}

		$row = $query->row_array();
		$rules = json_decode((string) $row['rules'], TRUE);
		if ( ! is_array($rules))
		{
			throw new RuntimeException('Ketentuan Praktek Sholat Berjamaah tidak valid.');
		}
		$rules[0] = $rule;

		$updated = $this->db->where('id', (int) $row['id'])->update('competitions', array(
			'minimum_participants' => $minimum,
			'type_label' => $minimum === NULL ? 'Regu 10 orang' : 'Regu 5–10 orang',
			'rules' => json_encode($rules, JSON_UNESCAPED_UNICODE),
		));
		if ( ! $updated || ! $this->db->trans_status())
		{
			throw new RuntimeException('Batas peserta Praktek Sholat Berjamaah gagal diperbarui: '.$this->db->error()['message']);
		}
	}
}
