<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared four-round Cerdas Cermat score board. */
class Cerdas_cermat_model extends CI_Model
{
	public function state(int $competitionId): array
	{
		$row = $this->db->where('competition_id', $competitionId)->get('cerdas_cermat_progress')->row_array();

		return $row ?: array('competition_id' => $competitionId, 'current_round' => 1, 'is_finished' => 0);
	}

	/** @return array<int,array<int,float>> entry ID => round number => points */
	public function scores_for_competition(int $competitionId): array
	{
		$rows = $this->db->select('s.entry_id, s.round_number, s.points')
			->from('cerdas_cermat_round_scores s')
			->join('entries e', 'e.id = s.entry_id')
			->where('e.competition_id', $competitionId)
			->get()->result_array();
		$scores = array();
		foreach ($rows as $row)
		{
			$scores[(int) $row['entry_id']][(int) $row['round_number']] = (float) $row['points'];
		}

		return $scores;
	}

	public function ensure_progress(int $competitionId): bool
	{
		return (bool) $this->db->query(
			'INSERT IGNORE INTO cerdas_cermat_progress (competition_id, current_round) VALUES (?, 1)',
			array($competitionId)
		);
	}

	public function set_progress(int $competitionId, int $round, bool $finished, int $judgeId): bool
	{
		return (bool) $this->db->where('competition_id', $competitionId)->update('cerdas_cermat_progress', array(
			'current_round' => $round,
			'is_finished' => $finished ? 1 : 0,
			'updated_by' => $judgeId,
		));
	}

	public function add_points(int $entryId, int $round, float $delta, int $judgeId): bool
	{
		$sql = 'INSERT INTO cerdas_cermat_round_scores (entry_id, round_number, points, created_by, updated_by)
			VALUES (?, ?, GREATEST(0, ?), ?, ?)
			ON DUPLICATE KEY UPDATE points = GREATEST(0, points + ?), updated_by = ?, updated_at = CURRENT_TIMESTAMP';

		return (bool) $this->db->query($sql, array($entryId, $round, $delta, $judgeId, $judgeId, $delta, $judgeId));
	}

	/** @param list<int> $entryIds */
	public function save_missing_round_scores(array $entryIds, int $round, int $judgeId): bool
	{
		foreach ($entryIds as $entryId)
		{
			$ok = $this->db->query(
				'INSERT IGNORE INTO cerdas_cermat_round_scores (entry_id, round_number, points, created_by, updated_by)
				VALUES (?, ?, 0, ?, ?)',
				array($entryId, $round, $judgeId, $judgeId)
			);
			if ($ok === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}
