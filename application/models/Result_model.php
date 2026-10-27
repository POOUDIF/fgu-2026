<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Scoring;

/**
 * Hasil lomba: total tiap juri, akumulasi, penalti, nilai akhir, peringkat, pemenang.
 */
class Result_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Entry_model', 'Penalty_model', 'Cerdas_cermat_model'));
	}

	/**
	 * @param array<string,mixed> $competition hasil Competition_model::find / hydrate
	 * @return list<array<string,mixed>> satu baris per entry (semua status), urut peringkat
	 */
	public function for_competition(array $competition): array
	{
		$cid = (int) $competition['id'];

		$entries = $this->Entry_model->attach_members(
			$this->db->select('e.*, v.name AS village_name, LOWER(v.name) AS village_order')
				->from('entries e')->join('villages v', 'v.id = e.village_id')
				->where('e.competition_id', $cid)->get()->result_array()
		);
		if ($this->Competition_model->is_cerdas_cermat($competition))
		{
			return $this->cerdas_cermat_results($competition, $entries);
		}

		$rows = $this->db->select('s.entry_id, s.judge_id, s.score, cr.max_score, cr.weight, u.full_name AS judge_name')
			->from('scores s')->join('entries e', 'e.id = s.entry_id')->join('criteria cr', 'cr.id = s.criterion_id')->join('users u', 'u.id = s.judge_id')
			->where('e.competition_id', $cid)->get()->result_array();
		$scores = array();
		$judgeNames = array();
		foreach ($rows as $r)
		{
			$scores[(int) $r['entry_id']][(int) $r['judge_id']][] = array(
				'score' => (float) $r['score'], 'max_score' => (float) $r['max_score'], 'weight' => (float) $r['weight'],
			);
			$judgeNames[(int) $r['judge_id']] = $r['judge_name'];
		}

		$expectedJudges = count($this->Competition_model->judge_ids($cid));
		$penalties = $this->Penalty_model->for_competition($cid);

		$results = array();
		foreach ($entries as $e)
		{
			$id = (int) $e['id'];
			$judgeTotals = array();
			foreach ($scores[$id] ?? array() as $judgeId => $set)
			{
				$judgeTotals[$judgeId] = Scoring::judgeTotal($set, $competition['scoring_method']);
			}
			$penalty = array_sum(array_map(static fn ($p) => (float) $p['points'], $penalties[$id] ?? array()));

			$results[] = $e + Scoring::accumulate(array_values($judgeTotals), (float) $penalty) + array(
				'judge_totals' => $judgeTotals,
				'judge_names' => $judgeNames,
				'penalty_rows' => $penalties[$id] ?? array(),
				'expected_judges' => $expectedJudges,
			);
		}

		// Hanya entry berstatus 'registered' yang diperingkat.
		$rankable = $results;
		foreach ($rankable as $i => $r)
		{
			if ($r['status'] !== 'registered' || $r['attendance_status'] !== 'present')
			{
				$rankable[$i]['final_score'] = NULL;
			}
		}
		$ranked = Scoring::rank($rankable);
		$winnerCount = (int) $competition['winner_count'];
		foreach ($ranked as $i => $r)
		{
			$results[$i]['rank'] = $r['rank'];
			$results[$i]['is_winner'] = $r['rank'] !== NULL && $r['rank'] <= $winnerCount;
		}

		usort($results, static function ($a, $b) {
			return array($a['rank'] ?? PHP_INT_MAX, $a['village_order'], $a['id'])
				<=> array($b['rank'] ?? PHP_INT_MAX, $b['village_order'], $b['id']);
		});

		return $results;
	}

	/**
	 * Cerdas Cermat uses one shared score board; unplayed rounds count as zero.
	 *
	 * @param list<array<string,mixed>> $entries
	 * @return list<array<string,mixed>>
	 */
	private function cerdas_cermat_results(array $competition, array $entries): array
	{
		$competitionId = (int) $competition['id'];
		$scores = $this->Cerdas_cermat_model->scores_for_competition($competitionId);
		$penalties = $this->Penalty_model->for_competition($competitionId);
		$roundKeys = array(1 => 'babak_1', 2 => 'babak_2', 3 => 'babak_3', 4 => 'babak_4');
		$results = array();
		foreach ($entries as $entry)
		{
			$id = (int) $entry['id'];
			$roundScores = array();
			$roundPenalties = array_fill(1, 4, 0.0);
			$unassignedPenalty = 0.0;
			$penaltyRows = $penalties[$id] ?? array();
			foreach ($penaltyRows as $penalty)
			{
				$round = array_search($penalty['round_key'], $roundKeys, TRUE);
				if ($round === FALSE)
				{
					$unassignedPenalty += (float) $penalty['points'];
					continue;
				}
				$roundPenalties[$round] += (float) $penalty['points'];
			}
			foreach ($roundKeys as $round => $roundKey)
			{
				$roundScores[$round] = (float) ($scores[$id][$round] ?? 0) - $roundPenalties[$round];
			}
			$totalPenalty = array_sum(array_map(static fn ($penalty) => (float) $penalty['points'], $penaltyRows));
			$total = array_sum($roundScores) - $unassignedPenalty;
			$eligible = $entry['status'] === 'registered' && $entry['attendance_status'] === 'present';
			$results[] = $entry + array(
				'sum_total' => $total,
				'avg_total' => $total,
				'penalty' => $totalPenalty,
				'final_score' => $eligible ? round($total, 4) : NULL,
				'judge_count' => 1,
				'expected_judges' => 1,
				'judge_totals' => array(),
				'judge_names' => array(),
				'penalty_rows' => $penaltyRows,
				'round_scores' => $roundScores,
				'round_penalties' => $roundPenalties,
			);
		}

		$ranked = Scoring::rank($results);
		$winnerCount = (int) $competition['winner_count'];
		foreach ($ranked as $i => $row)
		{
			$results[$i]['rank'] = $row['rank'];
			$results[$i]['is_winner'] = $row['rank'] !== NULL && $row['rank'] <= $winnerCount;
		}
		usort($results, static function ($a, $b) {
			return array($a['rank'] ?? PHP_INT_MAX, $a['village_order'], $a['id'])
				<=> array($b['rank'] ?? PHP_INT_MAX, $b['village_order'], $b['id']);
		});

		return $results;
	}

	/**
	 * Hasil seluruh lomba aktif.
	 *
	 * @return list<array{competition:array<string,mixed>,results:list<array<string,mixed>>}>
	 */
	public function all_competitions(): array
	{
		$out = array();
		foreach ($this->Competition_model->active() as $c)
		{
			$out[] = array('competition' => $c, 'results' => $this->for_competition($c));
		}

		return $out;
	}

	/** Pemenang dari lomba yang telah dipublikasikan untuk halaman publik. */
	public function published_winners(): array
	{
		$out = array();
		foreach ($this->Competition_model->active() as $competition)
		{
			if (empty($competition['results_published']))
			{
				continue;
			}

			$winners = array_values(array_filter(
				$this->for_competition($competition),
				static fn ($row) => ! empty($row['is_winner'])
			));
			if ($winners !== array())
			{
				$out[] = array('competition' => $competition, 'results' => $winners);
			}
		}

		return $out;
	}

	/**
	 * Hasil entry milik satu desa pada seluruh lomba.
	 *
	 * @return list<array{competition:array<string,mixed>,rows:list<array<string,mixed>>}>
	 */
	public function for_village(int $villageId): array
	{
		$out = array();
		foreach ($this->Competition_model->active() as $c)
		{
			if (empty($c['results_published']))
			{
				continue;
			}
			$rows = array_values(array_filter($this->for_competition($c), static fn ($r) => (int) $r['village_id'] === $villageId && $r['rank'] !== NULL));
			if ($rows !== array())
			{
				$out[] = array('competition' => $c, 'rows' => $rows);
			}
		}

		return $out;
	}
}
