<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Competition_model extends CI_Model
{
	/** @return list<array<string,mixed>> lomba aktif, urut tampilan */
	public function active(): array
	{
		$rows = $this->db->where('is_active', 1)->order_by('sort_order')->order_by('name')->get('competitions')->result_array();

		return array_map(array($this, 'hydrate'), $rows);
	}

	/** Lomba aktif lengkap dengan kriteria dan juri. @return list<array<string,mixed>> */
	public function active_with_details(): array
	{
		$list = $this->active();
		$criteria = $this->criteria_by_competition();
		$judges = $this->judges_by_competition();
		foreach ($list as &$c)
		{
			$c['criteria'] = $criteria[$c['id']] ?? array();
			$c['judges'] = $judges[$c['id']] ?? array();
		}
		unset($c);

		return $list;
	}

	/** @return list<array<string,mixed>> lomba aktif yang menerima kiriman karya online */
	public function online(): array
	{
		return array_values(array_filter($this->active(), static fn ($c) => $c['submission_mode'] === 'online'));
	}

	public function find(int $id): ?array
	{
		$row = $this->db->where('id', $id)->get('competitions')->row_array();

		return $row ? $this->with_details($this->hydrate($row)) : NULL;
	}

	public function find_by_slug(string $slug): ?array
	{
		$row = $this->db->where('slug', $slug)->where('is_active', 1)->get('competitions')->row_array();

		return $row ? $this->with_details($this->hydrate($row)) : NULL;
	}

	public function set_results_published(int $competitionId, bool $published): bool
	{
		$values = array('results_published' => $published ? 1 : 0);
		if ($published)
		{
			$values['registration_open'] = 0;
		}

		return (bool) $this->db->where('id', $competitionId)->update('competitions', $values);
	}

	public function set_registration_open(int $competitionId, bool $open): bool
	{
		return (bool) $this->db->where('id', $competitionId)->update('competitions', array(
			'registration_open' => $open ? 1 : 0,
		));
	}

	/** @return list<string> slug yang sudah dipakai (selain $exceptId) */
	public function taken_slugs(?int $exceptId = NULL): array
	{
		if ($exceptId !== NULL)
		{
			$this->db->where('id !=', $exceptId);
		}

		return array_column($this->db->select('slug')->get('competitions')->result_array(), 'slug');
	}

	/** @return list<array<string,mixed>> */
	public function criteria(int $competitionId): array
	{
		return $this->db->where('competition_id', $competitionId)->order_by('sort_order')->order_by('id')->get('criteria')->result_array();
	}

	/** @return array<int,list<array<string,mixed>>> */
	public function criteria_by_competition(): array
	{
		$out = array();
		foreach ($this->db->order_by('sort_order')->order_by('id')->get('criteria')->result_array() as $r)
		{
			$out[(int) $r['competition_id']][] = $r;
		}

		return $out;
	}

	/** @return array<int,list<array{id:int,full_name:string}>> juri per lomba */
	public function judges_by_competition(): array
	{
		$out = array();
		$rows = $this->db->select('cj.competition_id, u.id, u.full_name')->from('competition_judges cj')
			->join('users u', 'u.id = cj.judge_id')->order_by('u.full_name')->get()->result_array();
		foreach ($rows as $r)
		{
			$out[(int) $r['competition_id']][] = array('id' => (int) $r['id'], 'full_name' => $r['full_name']);
		}

		return $out;
	}

	/** @return list<int> */
	public function judge_ids(int $competitionId): array
	{
		$rows = $this->db->select('judge_id')->where('competition_id', $competitionId)->get('competition_judges')->result_array();

		return array_map('intval', array_column($rows, 'judge_id'));
	}

	public function is_judge_assigned(int $competitionId, int $judgeId): bool
	{
		return $this->db->where(array('competition_id' => $competitionId, 'judge_id' => $judgeId))->count_all_results('competition_judges') > 0;
	}

	public function is_cerdas_cermat(array $competition): bool
	{
		return strtolower((string) ($competition['slug'] ?? '')) === 'cerdas-cermat'
			|| stripos((string) ($competition['name'] ?? ''), 'cerdas cermat') !== FALSE;
	}

	/** @return list<array<string,mixed>> lomba aktif yang dikelola Admin Lomba */
	public function assigned_to_manager(int $managerId): array
	{
		$assignedIds = $this->manager_assigned_ids($managerId);
		if ($assignedIds === array())
		{
			return array();
		}
		$list = array_values(array_filter($this->active_with_details(), static fn ($competition) => in_array((int) $competition['id'], $assignedIds, TRUE)));

		return $list;
	}

	/** @return list<int> */
	public function manager_assigned_ids(int $managerId): array
	{
		$rows = $this->db->select('competition_id')->where('manager_id', $managerId)->get('competition_managers')->result_array();

		return array_map('intval', array_column($rows, 'competition_id'));
	}

	public function is_manager_assigned(int $competitionId, int $managerId): bool
	{
		return $this->db->where(array('competition_id' => $competitionId, 'manager_id' => $managerId))->count_all_results('competition_managers') > 0;
	}

	/** @param list<int> $competitionIds */
	public function sync_manager_assignments(int $managerId, array $competitionIds): bool
	{
		$wanted = array_values(array_unique(array_map('intval', $competitionIds)));
		$current = $this->manager_assigned_ids($managerId);

		$this->db->trans_start();
		foreach (array_diff($wanted, $current) as $competitionId)
		{
			$this->db->insert('competition_managers', array('competition_id' => $competitionId, 'manager_id' => $managerId));
		}
		$revoke = array_diff($current, $wanted);
		if ($revoke !== array())
		{
			$this->db->where('manager_id', $managerId)->where_in('competition_id', array_values($revoke))->delete('competition_managers');
		}
		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/** @return list<array<string,mixed>> lomba aktif yang ditugaskan ke juri */
	public function assigned_to_judge(int $judgeId): array
	{
		$rows = $this->db->select('c.*')
			->from('competitions c')
			->join('competition_judges cj', 'cj.competition_id = c.id')
			->where('cj.judge_id', $judgeId)->where('c.is_active', 1)
			->order_by('c.sort_order')->order_by('c.name')
			->get()->result_array();
		$list = array_map(array($this, 'hydrate'), $rows);
		$criteria = $this->criteria_by_competition();
		foreach ($list as &$c)
		{
			$c['criteria'] = $criteria[$c['id']] ?? array();
		}
		unset($c);

		return $list;
	}

	/** @return list<int> */
	public function assigned_ids(int $judgeId): array
	{
		$rows = $this->db->select('competition_id')->where('judge_id', $judgeId)->get('competition_judges')->result_array();

		return array_map('intval', array_column($rows, 'competition_id'));
	}

	/**
	 * Mengganti penugasan juri (diff): tambah yang baru, cabut yang tidak dicentang.
	 *
	 * @param list<int> $competitionIds
	 */
	public function sync_judge_assignments(int $judgeId, array $competitionIds): bool
	{
		$wanted = array_values(array_unique(array_map('intval', $competitionIds)));
		$current = $this->assigned_ids($judgeId);

		$this->db->trans_start();
		foreach (array_diff($wanted, $current) as $cid)
		{
			$this->db->insert('competition_judges', array('competition_id' => $cid, 'judge_id' => $judgeId));
		}
		$revoke = array_diff($current, $wanted);
		if ($revoke !== array())
		{
			$this->db->where('judge_id', $judgeId)->where_in('competition_id', array_values($revoke))->delete('competition_judges');
		}
		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/** Ubah kolom JSON/angka dari string MySQL menjadi tipe PHP yang benar. */
	public function hydrate(array $row): array
	{
		$decoded = json_decode((string) ($row['rules'] ?? '[]'), TRUE);
		$row['rules'] = is_array($decoded) ? $decoded : array();
		$formFields = json_decode((string) ($row['form_fields'] ?? '[]'), TRUE);
		$row['form_fields'] = is_array($formFields) ? $formFields : array();
		$levels = json_decode((string) ($row['levels'] ?? '[]'), TRUE);
		$row['levels'] = is_array($levels) ? $levels : array();
		$genders = json_decode((string) ($row['allowed_genders'] ?? '[]'), TRUE);
		$row['allowed_genders'] = is_array($genders) ? $genders : array();
		$row['gender_label'] = $row['allowed_genders'] === array('L')
			? 'Putra saja'
			: ($row['allowed_genders'] === array('P')
				? 'Putri saja'
				: (($row['participation_type'] ?? '') === 'team' ? 'Putra dan putri' : 'Putra/Putri'));
		$row['gender_option'] = $row['allowed_genders'] === array('L')
			? 'male'
			: ($row['allowed_genders'] === array('P') ? 'female' : 'both');
		foreach (array('id', 'winner_count', 'sort_order') as $col)
		{
			$row[$col] = (int) $row[$col];
		}
		$row['quota'] = ($row['quota'] === NULL || $row['quota'] === '') ? NULL : (int) $row['quota'];
		$row['minimum_participants'] = ($row['minimum_participants'] ?? NULL) === NULL || $row['minimum_participants'] === ''
			? NULL
			: (int) $row['minimum_participants'];
		$row['is_active'] = (bool) $row['is_active'];
		$row['registration_open'] = (bool) ($row['registration_open'] ?? FALSE);
		$row['results_published'] = (bool) ($row['results_published'] ?? FALSE);
		$row['allow_team'] = (bool) ($row['allow_team'] ?? FALSE);
		$row['scoring_method'] = ($row['scoring_method'] ?? 'weighted') === 'points' ? 'points' : 'weighted';
		$row['is_team'] = $row['participation_type'] === 'team';

		return $row;
	}

	private function with_details(array $c): array
	{
		$c['criteria'] = $this->criteria($c['id']);
		$judges = $this->judges_by_competition();
		$c['judges'] = $judges[$c['id']] ?? array();

		return $c;
	}
}
