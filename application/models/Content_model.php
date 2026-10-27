<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Konten situs berbentuk pasangan kunci-nilai (tagline, about, announcement). */
class Content_model extends CI_Model
{
	public const KEYS = array('tagline', 'about', 'announcement');

	/** @return array<string,string> */
	public function all(): array
	{
		$out = array_fill_keys(self::KEYS, '');
		foreach ($this->db->get('site_content')->result_array() as $row)
		{
			$out[$row['content_key']] = $row['content_value'];
		}

		return $out;
	}

	/**
	 * Upsert; nilai kosong DIABAIKAN (tidak menghapus konten yang ada).
	 *
	 * @param array<string,string> $values
	 */
	public function save(array $values): bool
	{
		$this->db->trans_start();
		foreach (self::KEYS as $key)
		{
			$value = trim((string) ($values[$key] ?? ''));
			if ($value === '')
			{
				continue;
			}
			$this->db->query(
				'INSERT INTO site_content (content_key, content_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE content_value = VALUES(content_value)',
				array($key, $value)
			);
		}
		$this->db->trans_complete();

		return $this->db->trans_status();
	}
}
