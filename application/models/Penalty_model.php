<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Penalty_model extends CI_Model
{
	public function add(int $entryId, float $points, ?string $reason, int $userId): bool
	{
		return (bool) $this->db->insert('penalties', array(
			'entry_id' => $entryId, 'points' => $points, 'reason' => $reason, 'created_by' => $userId,
		));
	}

	public function add_round(int $entryId, string $roundKey, float $points, string $reason, int $userId): bool
	{
		return (bool) $this->db->insert('penalties', array(
			'entry_id' => $entryId, 'round_key' => $roundKey, 'points' => $points,
			'reason' => $reason, 'created_by' => $userId,
		));
	}

	/** @return array<string,list<array<string,mixed>>> penalti disiplin per babak */
	public function rounds_for_entry(int $entryId): array
	{
		$rows = $this->db->where('entry_id', $entryId)->where('round_key IS NOT NULL', NULL, FALSE)
			->order_by('id')->get('penalties')->result_array();
		$out = array();
		foreach ($rows as $row)
		{
			$out[$row['round_key']][] = $row;
		}

		return $out;
	}

	public function total_for_entry(int $entryId): float
	{
		$row = $this->db->select('COALESCE(SUM(points), 0) AS total', FALSE)
			->where('entry_id', $entryId)->get('penalties')->row_array();

		return (float) ($row['total'] ?? 0);
	}

	public function delete(int $id): bool
	{
		return (bool) $this->db->where('id', $id)->delete('penalties');
	}

	/** @return array<int,list<array<string,mixed>>> penalti per pendaftaran dalam satu lomba. */
	public function for_competition(int $competitionId): array
	{
		$out = array();
		$rows = $this->db->select('p.*')->from('penalties p')->join('entries e', 'e.id = p.entry_id')
			->where('e.competition_id', $competitionId)->order_by('p.id')->get()->result_array();
		foreach ($rows as $r)
		{
			$out[(int) $r['entry_id']][] = $r;
		}

		return $out;
	}

	public function entry_competition_id(int $penaltyId): ?int
	{
		$row = $this->db->select('e.competition_id')->from('penalties p')->join('entries e', 'e.id = p.entry_id')->where('p.id', $penaltyId)->get()->row_array();

		return $row ? (int) $row['competition_id'] : NULL;
	}
}
