<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Use the published Bazaar rubric for the separately registered Bazaar Desa competition. */
class Migration_Bazaar_judging_criteria extends CI_Migration
{
	private $criteria = array(
		array('Dekorasi stand', 40),
		array('Dress code peserta', 25),
		array('Keunikan produk', 20),
		array('Kebersihan & kerapihan', 15),
	);

	public function up()
	{
		$competition = $this->db->where('slug', 'bazaar-desa')->get('competitions')->row_array();
		if ( ! $competition)
		{
			throw new RuntimeException('Lomba Bazaar Desa tidak ditemukan saat menambahkan kriteria penilaian.');
		}

		$competitionId = (int) $competition['id'];
		$existing = $this->db->where('competition_id', $competitionId)
			->order_by('sort_order')->order_by('id')->get('criteria')->result_array();
		if (count($existing) !== 1 || $existing[0]['name'] !== 'Penilaian Bazaar Desa')
		{
			return;
		}

		if ($this->has_scores_or_drafts($competitionId))
		{
			throw new RuntimeException('Kriteria Bazaar Desa tidak dapat diperbarui karena sudah digunakan pada nilai atau draft juri.');
		}

		$this->db->trans_begin();
		$this->db->where('id', (int) $existing[0]['id'])->update('criteria', array(
			'name' => $this->criteria[0][0],
			'weight' => $this->criteria[0][1],
			'sort_order' => 1,
		));
		foreach (array_slice($this->criteria, 1) as $index => $criterion)
		{
			$this->db->insert('criteria', array(
				'competition_id' => $competitionId,
				'name' => $criterion[0],
				'weight' => $criterion[1],
				'max_score' => 100,
				'sort_order' => $index + 2,
			));
		}
		if ( ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			throw new RuntimeException('Kriteria penilaian Bazaar Desa gagal diperbarui: '.($error['message'] ?? 'Database rejected a statement.'));
		}
		$this->db->trans_commit();
	}

	public function down()
	{
		$competition = $this->db->where('slug', 'bazaar-desa')->get('competitions')->row_array();
		if ( ! $competition)
		{
			return;
		}

		$competitionId = (int) $competition['id'];
		$existing = $this->db->where('competition_id', $competitionId)
			->order_by('sort_order')->order_by('id')->get('criteria')->result_array();
		$names = array_column($existing, 'name');
		if ($names !== array_column($this->criteria, 0))
		{
			return;
		}
		if ($this->has_scores_or_drafts($competitionId))
		{
			throw new RuntimeException('Rollback tidak dapat dilakukan karena kriteria Bazaar Desa sudah digunakan pada nilai atau draft juri.');
		}

		$this->db->trans_begin();
		foreach (array_slice($existing, 1) as $criterion)
		{
			$this->db->where('id', (int) $criterion['id'])->delete('criteria');
		}
		$this->db->where('id', (int) $existing[0]['id'])->update('criteria', array(
			'name' => 'Penilaian Bazaar Desa',
			'weight' => 100,
			'sort_order' => 1,
		));
		if ( ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			throw new RuntimeException('Rollback kriteria Bazaar Desa gagal: '.($error['message'] ?? 'Database rejected a statement.'));
		}
		$this->db->trans_commit();
	}

	private function has_scores_or_drafts(int $competitionId): bool
	{
		$hasScores = $this->db->from('scores s')->join('criteria c', 'c.id = s.criterion_id')
			->where('c.competition_id', $competitionId)->count_all_results() > 0;
		$hasDrafts = $this->db->from('score_drafts d')->join('criteria c', 'c.id = d.criterion_id')
			->where('c.competition_id', $competitionId)->count_all_results() > 0;

		return $hasScores || $hasDrafts;
	}
}
