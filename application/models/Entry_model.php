<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Entry = satuan yang dinilai juri (1 peserta pada lomba perorangan; satu regu pada lomba regu).
 */
class Entry_model extends CI_Model
{
	private const SELECT = 'e.*, v.name AS village_name, c.name AS competition_name, c.slug AS competition_slug, c.participation_type, c.type_label, c.submission_mode, c.registration_mode, c.form_fields, c.allow_team, c.quota';

	public function find(int $id): ?array
	{
		$row = $this->base()->where('e.id', $id)->get()->row_array();

		return $row ? $this->attach_members(array($row))[0] : NULL;
	}

	/** Pendaftaran milik satu desa. */
	public function find_for_village(int $id, int $villageId): ?array
	{
		$row = $this->base()->where(array('e.id' => $id, 'e.village_id' => $villageId))->get()->row_array();

		return $row ? $this->attach_members(array($row))[0] : NULL;
	}

	/** @return list<array<string,mixed>> pendaftaran suatu desa pada satu lomba */
	public function for_village_competition(int $villageId, int $competitionId, ?string $source = NULL): array
	{
		$this->base()->where(array('e.village_id' => $villageId, 'e.competition_id' => $competitionId));
		if ($source !== NULL)
		{
			$this->db->where('e.source', $source);
		}

		return $this->attach_members($this->db->order_by('e.id')->get()->result_array());
	}

	/** @return list<array<string,mixed>> semua pendaftaran suatu desa */
	public function for_village(int $villageId): array
	{
		return $this->attach_members(
			$this->base()->where('e.village_id', $villageId)->order_by('c.sort_order')->order_by('e.id')->get()->result_array()
		);
	}

	/**
	 * Pendaftaran untuk panitia, dengan filter opsional.
	 *
	 * @return list<array<string,mixed>>
	 */
	public function admin_list(?int $competitionId = NULL, ?int $villageId = NULL, ?string $source = NULL): array
	{
		$this->base();
		if ($competitionId)
		{
			$this->db->where('e.competition_id', $competitionId);
		}
		if ($villageId)
		{
			$this->db->where('e.village_id', $villageId);
		}
		if ($source !== NULL)
		{
			$this->db->where('e.source', $source);
		}
		$rows = $this->db->order_by('c.sort_order')->order_by('v.name')->order_by('e.id')->get()->result_array();

		return $this->attach_members($rows);
	}

	/** Entry aktif (terdaftar) pada lomba tertentu, urut tampilan juri. @return list<array<string,mixed>> */
	public function registered_for_competition(int $competitionId): array
	{
		$rows = $this->base()->where(array('e.competition_id' => $competitionId, 'e.status' => 'registered'))
			->where('e.attendance_status', 'present')
			->order_by('v.name')->order_by('e.id')->get()->result_array();

		return $this->attach_members($rows);
	}

	/** Pendaftaran aktif untuk meja absensi, apa pun status kehadirannya. */
	public function for_attendance(?int $competitionId = NULL, ?int $villageId = NULL, ?string $attendanceStatus = NULL, ?string $name = NULL): array
	{
		$this->base()->where('e.status', 'registered');
		if ($competitionId !== NULL)
		{
			$this->db->where('e.competition_id', $competitionId);
		}
		if ($villageId !== NULL)
		{
			$this->db->where('e.village_id', $villageId);
		}
		if ($attendanceStatus !== NULL)
		{
			$this->db->where('e.attendance_status', $attendanceStatus);
		}
		if ($name !== NULL && $name !== '')
		{
			$this->db->group_start()->like('e.team_name', $name)
				->or_where("EXISTS (SELECT 1 FROM entry_members em JOIN participants p ON p.id = em.participant_id WHERE em.entry_id = e.id AND p.name LIKE ".$this->db->escape('%'.$name.'%').')', NULL, FALSE)
				->group_end();
		}
		$rows = $this->db->order_by('v.name')->order_by('c.sort_order')->order_by('e.id')->get()->result_array();

		return $this->attach_members($rows);
	}

	/** Menandai kehadiran secara atomik; peserta tidak hadir dikecualikan dari penilaian dan peringkat. */
	public function mark_attendance(int $entryId, string $attendance, int $actorId): bool
	{
		if ( ! in_array($attendance, array('present', 'absent'), TRUE))
		{
			return FALSE;
		}
		$this->db->trans_begin();
		$row = $this->db->query('SELECT id FROM entries WHERE id = ? AND status = ? FOR UPDATE', array($entryId, 'registered'))->row_array();
		if ( ! $row)
		{
			$this->db->trans_rollback();

			return FALSE;
		}
		$ok = $this->db->where('id', $entryId)->update('entries', array(
			'attendance_status' => $attendance,
			'attendance_marked_at' => date('Y-m-d H:i:s'),
			'attendance_marked_by' => $actorId,
		));
		if ( ! $ok || ! $this->db->trans_status())
		{
			$this->db->trans_rollback();

			return FALSE;
		}
		$this->db->trans_commit();

		return TRUE;
	}

	/**
	 * Jumlah pendaftaran aktif per (lomba, desa).
	 *
	 * @return array<int,array<int,int>> [competition_id][village_id] => jumlah entry
	 */
	public function counts_by_competition_village(): array
	{
		$out = array();
		$rows = $this->db->select('competition_id, village_id, COUNT(*) AS n', FALSE)
			->where('status', 'registered')->group_by('competition_id, village_id')->get('entries')->result_array();
		foreach ($rows as $r)
		{
			$out[(int) $r['competition_id']][(int) $r['village_id']] = (int) $r['n'];
		}

		return $out;
	}

	/**
	 * Jumlah peserta aktif per (lomba, desa); entry tanpa anggota tercatat dihitung satu.
	 *
	 * @return array<int,array<int,int>> [competition_id][village_id] => jumlah peserta
	 */
	public function participant_counts_by_competition_village(): array
	{
		$out = array();
		$rows = $this->db->query(
			'SELECT e.competition_id, e.village_id, '
			.'SUM(CASE WHEN member_counts.participant_count IS NULL THEN 1 ELSE member_counts.participant_count END) AS n '
			.'FROM entries e '
			.'LEFT JOIN (SELECT entry_id, COUNT(*) AS participant_count FROM entry_members GROUP BY entry_id) member_counts '
			.'ON member_counts.entry_id = e.id '
			.'WHERE e.status = ? '
			.'GROUP BY e.competition_id, e.village_id',
			array('registered')
		)->result_array();
		foreach ($rows as $r)
		{
			$out[(int) $r['competition_id']][(int) $r['village_id']] = (int) $r['n'];
		}

		return $out;
	}

	/**
	 * Ringkasan desa per lomba: jumlah entry dan peserta (sumber 'village' untuk hitung kuota).
	 *
	 * @return array<int,array{entries:int,participants:int}>
	 */
	public function village_summary(int $villageId): array
	{
		$out = array();
		$rows = $this->db->select('e.competition_id, COUNT(DISTINCT e.id) AS entries, COUNT(em.participant_id) AS participants', FALSE)
			->from('entries e')->join('entry_members em', 'em.entry_id = e.id', 'left')
			->where('e.village_id', $villageId)->where('e.status', 'registered')
			->group_by('e.competition_id')->get()->result_array();
		foreach ($rows as $r)
		{
			$out[(int) $r['competition_id']] = array('entries' => (int) $r['entries'], 'participants' => (int) $r['participants']);
		}

		return $out;
	}

	public function set_status(int $id, string $status, ?string $reason): bool
	{
		return (bool) $this->db->where('id', $id)->update('entries', array(
			'status' => $status,
			'disqualified_reason' => $status === 'disqualified' ? $reason : NULL,
		));
	}

	public function update_submission_url(int $id, ?string $url): bool
	{
		return (bool) $this->db->where('id', $id)->update('entries', array('submission_url' => $url));
	}

	/** Update fields from a direct registration without allowing village/competition reassignment. */
	public function update_open_village_entry(int $id, int $villageId, array $values): bool
	{
		return (bool) $this->db->where(array(
			'id' => $id,
			'village_id' => $villageId,
			'status' => 'registered',
		))->update('entries', $values);
	}

	public function count_registered(): int
	{
		return (int) $this->db->where('status', 'registered')->count_all_results('entries');
	}

	public function count_registered_villages(): int
	{
		$row = $this->db->select('COUNT(DISTINCT village_id) AS n', FALSE)
			->where('status', 'registered')->get('entries')->row_array();

		return (int) ($row['n'] ?? 0);
	}

	/** Ringkasan pendaftaran aktif dan lomba yang diikuti per desa. */
	public function summaries_by_village(): array
	{
		$rows = $this->db->select('village_id, COUNT(*) AS entry_count, COUNT(DISTINCT competition_id) AS competition_count', FALSE)
			->where('status', 'registered')->group_by('village_id')->get('entries')->result_array();
		$summary = array();
		foreach ($rows as $row)
		{
			$summary[(int) $row['village_id']] = array(
				'entries' => (int) $row['entry_count'],
				'competitions' => (int) $row['competition_count'],
			);
		}

		return $summary;
	}

	/**
	 * Tambahkan 'members' dan 'label' (nama peserta) pada tiap entry.
	 *
	 * @param list<array<string,mixed>> $entries
	 * @return list<array<string,mixed>>
	 */
	public function attach_members(array $entries): array
	{
		if ($entries === array())
		{
			return $entries;
		}
		$members = array();
		$rows = $this->db->select('em.entry_id, p.id, p.name, p.age, p.parent_name, p.gender, p.education_level, p.grade, p.photo_path')
			->from('entry_members em')->join('participants p', 'p.id = em.participant_id')
			->where_in('em.entry_id', array_map('intval', array_column($entries, 'id')))
			->order_by('p.id')->get()->result_array();
		foreach ($rows as $m)
		{
			$members[(int) $m['entry_id']][] = $m;
		}
		foreach ($entries as &$e)
		{
			$e['members'] = $members[(int) $e['id']] ?? array();
			$e['label'] = self::label($e);
		}
		unset($e);

		return $entries;
	}

	/** Nama peserta (regu: dipisah koma). */
	public static function label(array $entry): string
	{
		if (trim((string) ($entry['team_name'] ?? '')) !== '')
		{
			return trim($entry['team_name']);
		}
		$names = array_column($entry['members'] ?? array(), 'name');

		return $names === array() ? ('Pendaftaran #'.$entry['id']) : implode(', ', $names);
	}

	private function base()
	{
		return $this->db->select(self::SELECT)
			->from('entries e')
			->join('villages v', 'v.id = e.village_id')
			->join('competitions c', 'c.id = e.competition_id');
	}
}
