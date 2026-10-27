<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Scoring;

class Score_model extends CI_Model
{
	public function judge_has_scored(int $entryId, int $judgeId): bool
	{
		return $this->db->where(array('entry_id' => $entryId, 'judge_id' => $judgeId))->count_all_results('scores') > 0;
	}

	/**
	 * Simpan semua nilai satu juri atas satu entry dalam satu INSERT (atomik).
	 *
	 * @param array<int,float> $scores criterion_id => nilai
	 */
	public function insert_set(int $entryId, int $judgeId, array $scores): bool
	{
		$batch = array();
		foreach ($scores as $criterionId => $value)
		{
			$batch[] = array('entry_id' => $entryId, 'judge_id' => $judgeId, 'criterion_id' => (int) $criterionId, 'score' => $value);
		}

		return $batch !== array() && (bool) $this->db->insert_batch('scores', $batch);
	}

	/** @param array<int,float> $scores criterion_id => nilai */
	public function replace_draft_set(int $entryId, int $judgeId, array $scores): bool
	{
		$this->db->where(array('entry_id' => $entryId, 'judge_id' => $judgeId))->delete('score_drafts');
		$batch = array();
		foreach ($scores as $criterionId => $value)
		{
			$batch[] = array('entry_id' => $entryId, 'judge_id' => $judgeId, 'criterion_id' => (int) $criterionId, 'score' => $value);
		}

		return $batch !== array() && (bool) $this->db->insert_batch('score_drafts', $batch);
	}

	/** @return array<int,float> nilai draft keyed by criterion_id */
	public function draft_scores_for_entry_judge(int $entryId, int $judgeId): array
	{
		$rows = $this->db->select('criterion_id, score')->where(array(
			'entry_id' => $entryId, 'judge_id' => $judgeId,
		))->get('score_drafts')->result_array();
		$scores = array();
		foreach ($rows as $row)
		{
			$scores[(int) $row['criterion_id']] = (float) $row['score'];
		}

		return $scores;
	}

	/** @return array<int,true> id entry yang memiliki draft juri pada satu lomba */
	public function draft_entry_ids(int $competitionId, int $judgeId): array
	{
		$rows = $this->db->distinct()->select('d.entry_id')
			->from('score_drafts d')->join('entries e', 'e.id = d.entry_id')
			->where(array(
				'e.competition_id' => $competitionId, 'e.status' => 'registered',
				'e.attendance_status' => 'present', 'd.judge_id' => $judgeId,
			))
			->get()->result_array();

		return array_fill_keys(array_map('intval', array_column($rows, 'entry_id')), TRUE);
	}

	/** Jumlah entry berdraft per lomba untuk satu juri. @return array<int,int> */
	public function draft_counts_by_competition(int $judgeId): array
	{
		$rows = $this->db->select('e.competition_id, COUNT(DISTINCT d.entry_id) AS n', FALSE)
			->from('score_drafts d')->join('entries e', 'e.id = d.entry_id')
			->where(array('e.status' => 'registered', 'e.attendance_status' => 'present', 'd.judge_id' => $judgeId))
			->group_by('e.competition_id')->get()->result_array();
		$counts = array();
		foreach ($rows as $row)
		{
			$counts[(int) $row['competition_id']] = (int) $row['n'];
		}

		return $counts;
	}

	/** Draft satu entry juri, dengan rincian dan total seperti submission final. */
	public function draft_for_entry_judge(int $entryId, int $judgeId, string $scoringMethod): ?array
	{
		$rows = $this->db->select('d.criterion_id, d.score, cr.name AS criterion_name, cr.max_score, cr.weight')
			->from('score_drafts d')->join('criteria cr', 'cr.id = d.criterion_id')
			->where(array('d.entry_id' => $entryId, 'd.judge_id' => $judgeId))
			->order_by('cr.sort_order')->order_by('cr.id')->get()->result_array();
		if ($rows === array())
		{
			return NULL;
		}

		$details = array();
		foreach ($rows as $row)
		{
			$details[] = array(
				'criterion_id' => (int) $row['criterion_id'],
				'name' => $row['criterion_name'],
				'score' => (float) $row['score'],
				'max_score' => (float) $row['max_score'],
				'weight' => (float) $row['weight'],
			);
		}

		return array(
			'details' => $details,
			'total' => Scoring::judgeTotal($details, $scoringMethod),
			'is_draft' => TRUE,
		);
	}

	/** Nilai draft per entry untuk proses finalisasi atomik. @return array<int,array<int,float>> */
	public function draft_sets_for_competition(int $competitionId, int $judgeId): array
	{
		$rows = $this->db->select('d.entry_id, d.criterion_id, d.score')
			->from('score_drafts d')->join('entries e', 'e.id = d.entry_id')
			->where(array(
				'e.competition_id' => $competitionId, 'e.status' => 'registered',
				'e.attendance_status' => 'present', 'd.judge_id' => $judgeId,
			))
			->order_by('d.entry_id')->get()->result_array();
		$sets = array();
		foreach ($rows as $row)
		{
			$sets[(int) $row['entry_id']][(int) $row['criterion_id']] = (float) $row['score'];
		}

		return $sets;
	}

	/** Nilai final per entry untuk memeriksa finalisasi seluruh peserta. @return array<int,array<int,float>> */
	public function score_sets_for_competition(int $competitionId, int $judgeId): array
	{
		$rows = $this->db->select('s.entry_id, s.criterion_id, s.score')
			->from('scores s')->join('entries e', 'e.id = s.entry_id')
			->where(array(
				'e.competition_id' => $competitionId, 'e.status' => 'registered',
				'e.attendance_status' => 'present', 's.judge_id' => $judgeId,
			))
			->order_by('s.entry_id')->get()->result_array();
		$sets = array();
		foreach ($rows as $row)
		{
			$sets[(int) $row['entry_id']][(int) $row['criterion_id']] = (float) $row['score'];
		}

		return $sets;
	}

	public function delete_drafts_for_competition(int $competitionId, int $judgeId): bool
	{
		return (bool) $this->db->query(
			'DELETE d FROM score_drafts d JOIN entries e ON e.id = d.entry_id WHERE e.competition_id = ? AND d.judge_id = ?',
			array($competitionId, $judgeId)
		);
	}

	public function insert_one(int $entryId, int $judgeId, int $criterionId, float $value): bool
	{
		return (bool) $this->db->insert('scores', array(
			'entry_id' => $entryId, 'judge_id' => $judgeId, 'criterion_id' => $criterionId, 'score' => $value,
		));
	}

	/** Nilai satu juri atas satu entry, beserta data kriteria. @return list<array<string,mixed>> */
	public function for_entry_judge(int $entryId, int $judgeId): array
	{
		return $this->db->select('s.id, s.criterion_id, s.score, s.created_at, cr.name AS criterion_name, cr.max_score')
			->from('scores s')
			->join('criteria cr', 'cr.id = s.criterion_id')
			->where(array('s.entry_id' => $entryId, 's.judge_id' => $judgeId))
			->order_by('cr.sort_order')->order_by('cr.id')
			->get()->result_array();
	}

	/** @return array<int,true> id entry yang sudah dinilai juri pada satu lomba */
	public function scored_entry_ids(int $competitionId, int $judgeId): array
	{
		$rows = $this->db->distinct()->select('s.entry_id')
			->from('scores s')->join('entries e', 'e.id = s.entry_id')
			->where(array('e.competition_id' => $competitionId, 's.judge_id' => $judgeId))
			->get()->result_array();

		return array_fill_keys(array_map('intval', array_column($rows, 'entry_id')), TRUE);
	}

	public function find(int $scoreId): ?array
	{
		$row = $this->db->select('s.*, cr.name AS criterion_name, cr.max_score, e.competition_id, e.village_id, c.name AS competition_name, u.full_name AS judge_name, v.name AS village_name')
			->from('scores s')
			->join('criteria cr', 'cr.id = s.criterion_id')
			->join('entries e', 'e.id = s.entry_id')
			->join('competitions c', 'c.id = e.competition_id')
			->join('villages v', 'v.id = e.village_id')
			->join('users u', 'u.id = s.judge_id')
			->where('s.id', $scoreId)
			->get()->row_array();

		return $row ?: NULL;
	}

	public function update_value(int $scoreId, float $value): bool
	{
		return (bool) $this->db->where('id', $scoreId)->update('scores', array('score' => $value));
	}

	public function delete_for_entry_judge(int $entryId, int $judgeId): int
	{
		$this->db->where(array('entry_id' => $entryId, 'judge_id' => $judgeId))->delete('scores');

		return (int) $this->db->affected_rows();
	}

	/**
	 * Penilaian tersimpan: satu baris = satu juri menilai satu entry (lengkap dengan rincian dan total).
	 *
	 * @return list<array<string,mixed>> terbaru lebih dulu
	 */
	public function submissions(?int $judgeId = NULL, ?int $competitionId = NULL, ?int $villageId = NULL): array
	{
		$this->db->select('s.id AS score_id, s.entry_id, s.judge_id, s.score, s.created_at, cr.id AS criterion_id, cr.name AS criterion_name, cr.max_score, cr.weight,
			u.full_name AS judge_name, e.competition_id, e.village_id, e.status, e.attendance_status, c.name AS competition_name, c.scoring_method, v.name AS village_name', FALSE)
			->from('scores s')
			->join('entries e', 'e.id = s.entry_id')
			->join('criteria cr', 'cr.id = s.criterion_id')
			->join('users u', 'u.id = s.judge_id')
			->join('competitions c', 'c.id = e.competition_id')
			->join('villages v', 'v.id = e.village_id')
			->where(array('e.status' => 'registered', 'e.attendance_status' => 'present'));
		if ($judgeId !== NULL)
		{
			$this->db->where('s.judge_id', $judgeId);
		}
		if ($competitionId !== NULL)
		{
			$this->db->where('e.competition_id', $competitionId);
		}
		if ($villageId !== NULL)
		{
			$this->db->where('e.village_id', $villageId);
		}
		$rows = $this->db->order_by('s.created_at', 'DESC')->order_by('s.entry_id', 'DESC')->order_by('s.judge_id')->order_by('cr.sort_order')->order_by('cr.id')->get()->result_array();

		$groups = array();
		foreach ($rows as $r)
		{
			$key = $r['entry_id'].'-'.$r['judge_id'];
			if ( ! isset($groups[$key]))
			{
				$groups[$key] = array(
					'entry_id' => (int) $r['entry_id'], 'judge_id' => (int) $r['judge_id'], 'judge_name' => $r['judge_name'],
					'competition_id' => (int) $r['competition_id'], 'competition_name' => $r['competition_name'],
					'village_id' => (int) $r['village_id'], 'village_name' => $r['village_name'],
					'created_at' => $r['created_at'], 'details' => array(), 'total' => 0.0, 'scoring_method' => $r['scoring_method'],
				);
			}
			$groups[$key]['details'][] = array(
				'score_id' => (int) $r['score_id'], 'criterion_id' => (int) $r['criterion_id'], 'name' => $r['criterion_name'],
				'score' => (float) $r['score'], 'max_score' => (float) $r['max_score'], 'weight' => (float) $r['weight'],
			);
		}
		foreach ($groups as &$g)
		{
			$g['total'] = Scoring::judgeTotal($g['details'], $g['scoring_method']);
		}
		unset($g);

		// label peserta
		$this->load->model('Entry_model');
		$entryIds = array_values(array_unique(array_map('intval', array_column($groups, 'entry_id'))));
		$entryRows = $entryIds === array() ? array() : $this->db->select('id, team_name')->where_in('id', $entryIds)->get('entries')->result_array();
		$entries = $this->Entry_model->attach_members($entryRows);
		$labels = array_column($entries, 'label', 'id');
		foreach ($groups as &$g)
		{
			$g['entry_label'] = $labels[$g['entry_id']] ?? ('#'.$g['entry_id']);
		}
		unset($g);

		return array_values($groups);
	}

	/** Ringkasan penilaian paling baru, tanpa memuat seluruh riwayat. */
	public function recent_submissions(int $limit = 10, int $offset = 0): array
	{
		$limit = max(1, min(50, $limit));
		$offset = max(0, $offset);
		$sql = "SELECT recent.entry_id, recent.judge_id, recent.submitted_at,
				c.name AS competition_name, c.scoring_method, v.name AS village_name,
				u.full_name AS judge_name,
				CASE WHEN e.team_name IS NOT NULL AND e.team_name <> '' THEN e.team_name
					ELSE COALESCE(GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', '), CONCAT('Pendaftaran #', e.id))
				END AS entry_label,
				COUNT(DISTINCT s.criterion_id) AS criteria_count,
				CASE c.scoring_method
					WHEN 'points' THEN SUM(s.score)
					ELSE ROUND(100 * SUM(s.score / cr.max_score * cr.weight) / NULLIF(SUM(cr.weight), 0), 2)
				END AS total
			FROM (
				SELECT s.entry_id, s.judge_id, MAX(s.updated_at) AS submitted_at
				FROM scores s
				JOIN entries eligible ON eligible.id = s.entry_id
					AND eligible.status = 'registered' AND eligible.attendance_status = 'present'
				GROUP BY s.entry_id, s.judge_id
				ORDER BY submitted_at DESC
				LIMIT ? OFFSET ?
			) recent
			JOIN scores s ON s.entry_id = recent.entry_id AND s.judge_id = recent.judge_id
			JOIN entries e ON e.id = recent.entry_id
				AND e.status = 'registered' AND e.attendance_status = 'present'
			JOIN competitions c ON c.id = e.competition_id
			JOIN villages v ON v.id = e.village_id
			JOIN users u ON u.id = recent.judge_id
			JOIN criteria cr ON cr.id = s.criterion_id
			LEFT JOIN entry_members em ON em.entry_id = e.id
			LEFT JOIN participants p ON p.id = em.participant_id
			GROUP BY recent.entry_id, recent.judge_id, recent.submitted_at, c.name, c.scoring_method,
				v.name, u.full_name, e.team_name, e.id
			ORDER BY recent.submitted_at DESC";

		return $this->db->query($sql, array($limit, $offset))->result_array();
	}

	/**
	 * Kelengkapan penilaian per lomba.
	 * expected = entry aktif × juri penugasan; actual = pasangan (entry, juri) yang sudah ada nilainya.
	 *
	 * @return array<int,array{entries:int,judges:int,expected:int,actual:int,complete:bool}> keyed by competition_id
	 */
	public function competition_progress(?int $villageId = NULL, ?int $judgeId = NULL): array
	{
		$entries = array();
		$this->db->select('competition_id, COUNT(*) AS n', FALSE)->where(array('status' => 'registered', 'attendance_status' => 'present'));
		if ($villageId !== NULL)
		{
			$this->db->where('village_id', $villageId);
		}
		foreach ($this->db->group_by('competition_id')->get('entries')->result_array() as $r)
		{
			$entries[(int) $r['competition_id']] = (int) $r['n'];
		}

		$judges = array();
		$this->db->select('competition_id, COUNT(*) AS n', FALSE);
		if ($judgeId !== NULL)
		{
			$this->db->where('judge_id', $judgeId);
		}
		foreach ($this->db->group_by('competition_id')->get('competition_judges')->result_array() as $r)
		{
			$judges[(int) $r['competition_id']] = (int) $r['n'];
		}

		$sql = "SELECT e.competition_id, COUNT(*) AS n
			FROM (SELECT entry_id, judge_id FROM scores GROUP BY entry_id, judge_id) p
			JOIN entries e ON e.id = p.entry_id AND e.status = 'registered' AND e.attendance_status = 'present'
			JOIN competition_judges cj ON cj.competition_id = e.competition_id AND cj.judge_id = p.judge_id";
		$binds = array();
		$where = array();
		if ($villageId !== NULL)
		{
			$where[] = 'e.village_id = ?';
			$binds[] = $villageId;
		}
		if ($judgeId !== NULL)
		{
			$where[] = 'p.judge_id = ?';
			$binds[] = $judgeId;
		}
		$sql .= ($where ? ' WHERE '.implode(' AND ', $where) : '').' GROUP BY e.competition_id';
		$actual = array();
		foreach ($this->db->query($sql, $binds)->result_array() as $r)
		{
			$actual[(int) $r['competition_id']] = (int) $r['n'];
		}

		$out = array();
		foreach (array_unique(array_merge(array_keys($entries), array_keys($judges))) as $cid)
		{
			$e = $entries[$cid] ?? 0;
			$j = $judges[$cid] ?? 0;
			$expected = $e * $j;
			$done = min($actual[$cid] ?? 0, $expected);
			$out[$cid] = array('entries' => $e, 'judges' => $j, 'expected' => $expected, 'actual' => $done, 'complete' => $expected > 0 && $done >= $expected);
		}

		return $out;
	}

	public function count_for_judge(int $judgeId): int
	{
		$row = $this->db->query("SELECT COUNT(*) AS n FROM (SELECT 1 FROM scores s JOIN entries e ON e.id = s.entry_id
			WHERE s.judge_id = ? AND e.status = 'registered' AND e.attendance_status = 'present' GROUP BY s.entry_id) t", array($judgeId))->row_array();

		return (int) ($row['n'] ?? 0);
	}

	public function count_submissions(): int
	{
		$row = $this->db->query("SELECT COUNT(*) AS n FROM (SELECT 1 FROM scores s JOIN entries e ON e.id = s.entry_id
			WHERE e.status = 'registered' AND e.attendance_status = 'present' GROUP BY s.entry_id, s.judge_id) t")->row_array();

		return (int) ($row['n'] ?? 0);
	}

	/** Progress penilaian untuk setiap pasangan lomba dan juri yang ditugaskan. */
	public function admin_progress_rows(): array
	{
		$sql = "SELECT c.id AS competition_id, c.name AS competition_name,
				u.id AS judge_id, u.full_name AS judge_name,
				COUNT(DISTINCT e.id) AS total_entries,
				COUNT(DISTINCT CASE WHEN scored.entry_id IS NOT NULL THEN e.id END) AS scored_entries
			FROM competitions c
			JOIN competition_judges cj ON cj.competition_id = c.id
			JOIN users u ON u.id = cj.judge_id
			LEFT JOIN entries e ON e.competition_id = c.id AND e.status = 'registered' AND e.attendance_status = 'present'
			LEFT JOIN (
				SELECT entry_id, judge_id FROM scores GROUP BY entry_id, judge_id
			) scored ON scored.entry_id = e.id AND scored.judge_id = cj.judge_id
			WHERE c.is_active = 1
			GROUP BY c.id, c.name, u.id, u.full_name
			ORDER BY c.sort_order, c.name, u.full_name";

		return $this->db->query($sql)->result_array();
	}
}
