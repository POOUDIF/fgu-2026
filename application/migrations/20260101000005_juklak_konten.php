<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Refresh full competition rules from the latest FGU 3.0 Juklak revision. */
class Migration_Juklak_konten extends CI_Migration
{
	public function up()
	{
		$path = APPPATH.'../database/juklak_2026_rules.json';
		$contents = is_file($path) ? file_get_contents($path) : FALSE;
		if ($contents === FALSE)
		{
			throw new RuntimeException('Data ketentuan juklak terbaru tidak ditemukan.');
		}

		$revisions = json_decode($contents, TRUE);
		if ( ! is_array($revisions) || count($revisions) !== 16)
		{
			throw new RuntimeException('Data ketentuan juklak terbaru tidak valid.');
		}

		foreach ($revisions as $pattern => $rules)
		{
			if ( ! is_string($pattern) || ! is_array($rules) || $rules === array())
			{
				throw new RuntimeException('Format ketentuan juklak tidak valid.');
			}
		}

		$this->db->trans_begin();
		try
		{
			foreach ($revisions as $pattern => $rules)
			{
				$this->where_name_matches($pattern);
				if ($this->db->count_all_results('competitions') !== 1)
				{
					throw new RuntimeException('Pola nama lomba juklak harus cocok tepat satu kali: '.$pattern);
				}

				$this->where_name_matches($pattern);
				$encoded = json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
				if ( ! $this->db->update('competitions', array('rules' => $encoded)))
				{
					throw new RuntimeException('Pembaruan ketentuan juklak gagal: '.$this->db->error()['message']);
				}
			}
			if ( ! $this->db->trans_status())
			{
				throw new RuntimeException('Pembaruan ketentuan juklak gagal.');
			}
			$this->db->trans_commit();
		}
		catch (Throwable $e)
		{
			$this->db->trans_rollback();
			throw $e;
		}
	}

	private function where_name_matches(string $pattern): void
	{
		$this->db->where('name REGEXP '.$this->db->escape($pattern), NULL, FALSE);
	}

	public function down()
	{
		// Content data is intentionally not reverted to avoid restoring obsolete Juklak text.
	}
}
