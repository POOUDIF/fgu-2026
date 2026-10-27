<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\Slug;

/**
 * Data awal FGU 3.0 2026 dari juknis (database/juknis_2026.json):
 * 9 desa, 16 lomba beserta ketentuan dan kriteria penilaian, serta konten beranda.
 * Penugasan juri dilakukan admin setelah akun juri dibuat.
 */
class Migration_Seed_reference_data extends CI_Migration
{
	public function up()
	{
		$file = APPPATH.'../database/juknis_2026.json';
		$data = json_decode((string) file_get_contents($file), TRUE);
		if ( ! is_array($data))
		{
			throw new RuntimeException('database/juknis_2026.json tidak dapat dibaca.');
		}

		$this->db->insert('events', array(
			'name' => 'Festival Generasi Unggul 3.0',
			'event_date' => '2026-11-01',
			'venue' => 'Komplek Daerah',
			'theme' => NULL,
		));

		foreach ($data['content'] as $key => $value)
		{
			$this->db->insert('site_content', array('content_key' => $key, 'content_value' => $value));
		}

		foreach ($data['villages'] as $i => $v)
		{
			$this->db->insert('villages', array('name' => $v['name'], 'pic_name' => $v['pic'] !== '' ? $v['pic'] : NULL, 'sort_order' => $i + 1));
		}

		$used = array();
		foreach ($data['competitions'] as $i => $c)
		{
			$slug = Slug::unique(Slug::make($c['name']), $used);
			$used[] = $slug;

			$this->db->insert('competitions', array(
				'slug' => $slug,
				'name' => $c['name'],
				'cluster' => $c['cluster'],
				'age_label' => $c['age'],
				'type_label' => $c['type'],
				'participation_type' => $c['participation'],
				'quota' => $c['quota'],
				'submission_mode' => $c['submission'],
				'place' => $c['place'],
				'icon' => $c['icon'],
				'rules' => json_encode($c['rules'], JSON_UNESCAPED_UNICODE),
				'winner_count' => $c['winner_count'],
				'sort_order' => $i + 1,
			));
			$cid = (int) $this->db->insert_id();

			foreach ($c['criteria'] as $j => $name)
			{
				$this->db->insert('criteria', array('competition_id' => $cid, 'name' => $name, 'max_score' => 100, 'sort_order' => $j + 1));
			}
		}
	}

	public function down()
	{
		foreach (array('criteria', 'competitions', 'villages', 'site_content', 'events') as $table)
		{
			$this->db->query('DELETE FROM '.$table);
		}
	}
}
