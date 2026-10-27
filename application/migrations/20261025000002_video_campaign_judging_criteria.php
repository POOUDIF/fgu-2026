<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Apply the published weighted rubric to Video Campaign. */
class Migration_Video_campaign_judging_criteria extends CI_Migration
{
	private $criteria = array(
		array('Daya Tarik Awal', 30),
		array('Kreativitas Editing', 20),
		array('Efek Visual & Timing', 20),
		array('Pesan', 30),
	);

	public function up()
	{
		$competition = $this->db->where('slug', 'video-campaign')->get('competitions')->row_array();
		if ( ! $competition)
		{
			throw new RuntimeException('Lomba Video Campaign tidak ditemukan saat memperbarui kriteria penilaian.');
		}

		$competitionId = (int) $competition['id'];
		$existing = $this->db->where('competition_id', $competitionId)
			->order_by('sort_order')->order_by('id')->get('criteria')->result_array();
		$names = array_column($existing, 'name');
		$expectedNames = array_column($this->criteria, 0);
		if ($names !== array('Ketentuan penilaian mengikuti penjurian panitia') && $names !== $expectedNames)
		{
			throw new RuntimeException('Kriteria Video Campaign tidak sesuai dengan kondisi yang diharapkan; migrasi dibatalkan.');
		}

		if ($names === $expectedNames && $this->rubric_is_current($existing, $competition))
		{
			return;
		}

		if ($this->has_scores_or_drafts($competitionId))
		{
			throw new RuntimeException('Kriteria Video Campaign tidak dapat diperbarui karena sudah digunakan pada nilai atau draft juri.');
		}

		$this->db->trans_begin();
		if ($names === array('Ketentuan penilaian mengikuti penjurian panitia'))
		{
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
		}
		else
		{
			foreach ($this->criteria as $index => $criterion)
			{
				$this->db->where('id', (int) $existing[$index]['id'])->update('criteria', array(
					'weight' => $criterion[1],
					'sort_order' => $index + 1,
				));
			}
		}
		$this->db->where('id', $competitionId)->update('competitions', array('scoring_method' => 'weighted'));

		if ( ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			throw new RuntimeException('Kriteria penilaian Video Campaign gagal diperbarui: '.($error['message'] ?? 'Database rejected a statement.'));
		}
		$this->db->trans_commit();
	}

	public function down()
	{
		$competition = $this->db->where('slug', 'video-campaign')->get('competitions')->row_array();
		if ( ! $competition)
		{
			return;
		}

		$competitionId = (int) $competition['id'];
		$existing = $this->db->where('competition_id', $competitionId)
			->order_by('sort_order')->order_by('id')->get('criteria')->result_array();
		if (array_column($existing, 'name') !== array_column($this->criteria, 0))
		{
			return;
		}
		if ($this->has_scores_or_drafts($competitionId))
		{
			throw new RuntimeException('Rollback tidak dapat dilakukan karena kriteria Video Campaign sudah digunakan pada nilai atau draft juri.');
		}

		$this->db->trans_begin();
		foreach (array_slice($existing, 1) as $criterion)
		{
			$this->db->where('id', (int) $criterion['id'])->delete('criteria');
		}
		$this->db->where('id', (int) $existing[0]['id'])->update('criteria', array(
			'name' => 'Ketentuan penilaian mengikuti penjurian panitia',
			'weight' => 100,
			'sort_order' => 1,
		));
		if ( ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			throw new RuntimeException('Rollback kriteria Video Campaign gagal: '.($error['message'] ?? 'Database rejected a statement.'));
		}
		$this->db->trans_commit();
	}

	private function rubric_is_current(array $criteria, array $competition): bool
	{
		if ($competition['scoring_method'] !== 'weighted')
		{
			return FALSE;
		}

		foreach ($this->criteria as $index => $criterion)
		{
			if ((float) $criteria[$index]['weight'] !== (float) $criterion[1]
				|| (int) $criteria[$index]['sort_order'] !== $index + 1)
			{
				return FALSE;
			}
		}

		return TRUE;
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
