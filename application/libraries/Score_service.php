<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;
use App\Support\DbError;
use App\Domain\CerdasCermat;

/**
 * Penilaian juri dan koreksi Super Admin. Batas nilai (50..maks kriteria) dicek di server,
 * bukan hanya atribut HTML, dan penulisan dilakukan dalam transaksi.
 */
class Score_service
{
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model(array('Competition_model', 'Entry_model', 'Score_model', 'Audit_model', 'Cerdas_cermat_model', 'Penalty_model'));
	}

	/**
	 * Parse angka nilai: menerima koma/titik desimal, maksimal 2 desimal, tanpa tanda.
	 *
	 * @return float|null NULL bila tidak valid
	 */
	public static function parse_score(mixed $raw): ?float
	{
		$s = str_replace(',', '.', trim((string) $raw));
		if ( ! preg_match('/^\d{1,6}(\.\d{1,2})?$/', $s))
		{
			return NULL;
		}

		return (float) $s;
	}

	/**
	 * Simpan atau perbarui nilai sementara untuk satu peserta; belum masuk ke tabel hasil final.
	 *
	 * @param array<int|string,mixed> $raw criterion_id => nilai mentah dari form
	 */
	public function save_draft(int $judgeId, int $entryId, array $raw): ActionResult
	{
		$entry = $this->CI->Entry_model->find($entryId);
		if ( ! $entry || $entry['status'] !== 'registered' || $entry['attendance_status'] !== 'present')
		{
			return ActionResult::fail('Penilaian hanya dapat disimpan untuk peserta aktif yang telah ditandai hadir.');
		}
		$db = $this->CI->db;
		$competitionId = (int) $entry['competition_id'];
		$db->trans_begin();
		$lockedCompetition = $db->query('SELECT id, is_active, results_published FROM competitions WHERE id = ? FOR UPDATE', array($competitionId))->row_array();
		$lockedEntry = $db->query('SELECT id FROM entries WHERE id = ? AND competition_id = ? AND status = ? AND attendance_status = ? FOR UPDATE',
			array($entryId, $competitionId, 'registered', 'present'))->row_array();
		if ( ! $lockedEntry)
		{
			$db->trans_rollback();

			return ActionResult::fail('Peserta belum ditandai hadir; draft tidak dapat disimpan.');
		}
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $this->CI->Competition_model->is_judge_assigned($competitionId, $judgeId))
		{
			$db->trans_rollback();

			return ActionResult::fail('Anda tidak terdaftar sebagai juri lomba ini.');
		}
		if ((bool) $lockedCompetition['results_published'])
		{
			$db->trans_rollback();

			return ActionResult::fail('Penilaian lomba ini ditutup karena hasil sudah dipublikasikan.');
		}
		$competition = $this->CI->Competition_model->find($competitionId);
		if ($this->CI->Competition_model->is_cerdas_cermat($competition))
		{
			$db->trans_rollback();

			return ActionResult::fail('Gunakan tombol poin babak pada halaman Cerdas Cermat; nilai tidak dapat diisi manual.');
		}
		$criteria = $competition['criteria'] ?? array();
		if ($criteria === array())
		{
			$db->trans_rollback();

			return ActionResult::fail('Lomba ini belum memiliki kriteria penilaian.');
		}
		$parsed = $this->parse_all($criteria, $raw);
		if (is_string($parsed))
		{
			$db->trans_rollback();

			return ActionResult::fail($parsed);
		}
		if ($this->CI->Score_model->judge_has_scored($entryId, $judgeId))
		{
			$db->trans_rollback();

			return ActionResult::fail('Nilai sudah disubmit dan terkunci.');
		}
		if ( ! $this->CI->Score_model->replace_draft_set($entryId, $judgeId, $parsed) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok('Draft nilai berhasil disimpan. Nilai belum masuk ke hasil lomba.');
	}

	/** Submit seluruh draft juri pada satu lomba ke tabel nilai final dalam satu transaksi. */
	public function submit_competition(int $judgeId, int $competitionId): ActionResult
	{
		$db = $this->CI->db;
		$db->trans_begin();
		$lockedCompetition = $db->query('SELECT id, is_active, results_published FROM competitions WHERE id = ? FOR UPDATE', array($competitionId))->row_array();
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $this->CI->Competition_model->is_judge_assigned($competitionId, $judgeId))
		{
			$db->trans_rollback();

			return ActionResult::fail('Anda tidak terdaftar sebagai juri lomba ini.');
		}
		if ((bool) $lockedCompetition['results_published'])
		{
			$db->trans_rollback();

			return ActionResult::fail('Penilaian lomba ini ditutup karena hasil sudah dipublikasikan.');
		}
		$competition = $this->CI->Competition_model->find($competitionId);
		$criteria = $competition['criteria'] ?? array();
		if ($criteria === array())
		{
			$db->trans_rollback();

			return ActionResult::fail('Lomba ini belum memiliki kriteria penilaian.');
		}
		if ($this->CI->Competition_model->is_cerdas_cermat($competition))
		{
			$db->trans_rollback();

			return ActionResult::fail('Gunakan tombol babak pada papan Cerdas Cermat; lomba ini tidak menggunakan submit nilai.');
		}
		$entries = $db->query(
			'SELECT id FROM entries WHERE competition_id = ? AND status = ? AND attendance_status = ? ORDER BY id FOR UPDATE',
			array($competitionId, 'registered', 'present')
		)->result_array();
		if ($entries === array())
		{
			$db->trans_rollback();

			return ActionResult::fail('Belum ada peserta hadir yang dapat dinilai.');
		}

		$expectedCriteria = array_map(static fn ($criterion) => (int) $criterion['id'], $criteria);
		sort($expectedCriteria);
		$finalSets = $this->CI->Score_model->score_sets_for_competition($competitionId, $judgeId);
		$draftSets = $this->CI->Score_model->draft_sets_for_competition($competitionId, $judgeId);
		$toSubmit = array();
		foreach ($entries as $row)
		{
			$entryId = (int) $row['id'];
			if (isset($finalSets[$entryId]))
			{
				if ( ! $this->score_set_matches_criteria($finalSets[$entryId], $expectedCriteria, $criteria))
				{
					$db->trans_rollback();

					return ActionResult::fail('Nilai final peserta tidak lengkap. Hubungi panitia sebelum submit.');
				}
				continue;
			}
			$draft = $draftSets[$entryId] ?? array();
			if ( ! $this->score_set_matches_criteria($draft, $expectedCriteria, $criteria))
			{
				$db->trans_rollback();

				return ActionResult::fail('Semua peserta harus memiliki draft nilai lengkap sebelum submit.');
			}
			$toSubmit[$entryId] = $draft;
		}

		if ($toSubmit === array())
		{
			$db->trans_rollback();

			return ActionResult::fail('Tidak ada draft baru untuk disubmit.');
		}
		foreach ($toSubmit as $entryId => $scores)
		{
			if ( ! $this->CI->Score_model->insert_set((int) $entryId, $judgeId, $scores))
			{
				$error = $db->error();
				$db->trans_rollback();

				return ActionResult::fail(DbError::friendly($error));
			}
		}
		if ( ! $this->CI->Score_model->delete_drafts_for_competition($competitionId, $judgeId) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok('Semua nilai berhasil disubmit dan masuk ke hasil lomba.');
	}

	/** Update the shared score for one entry in the active Cerdas Cermat round. */
	public function change_cerdas_cermat_points(int $judgeId, int $entryId, int $roundNumber, string $change): ActionResult
	{
		$delta = CerdasCermat::point_delta($roundNumber, $change);
		if ($delta === NULL)
		{
			return ActionResult::fail('Babak atau perubahan poin tidak valid.');
		}

		$entry = $this->CI->Entry_model->find($entryId);
		if ( ! $entry)
		{
			return ActionResult::fail('Peserta tidak ditemukan.');
		}
		$db = $this->CI->db;
		$db->trans_begin();
		$context = $this->lock_cerdas_cermat($judgeId, (int) $entry['competition_id']);
		if (is_string($context))
		{
			$db->trans_rollback();

			return ActionResult::fail($context);
		}
		$lockedEntry = $db->query(
			'SELECT id FROM entries WHERE id = ? AND competition_id = ? AND status = ? AND attendance_status = ? FOR UPDATE',
			array($entryId, (int) $entry['competition_id'], 'registered', 'present')
		)->row_array();
		if ( ! $lockedEntry)
		{
			$db->trans_rollback();

			return ActionResult::fail('Poin hanya dapat diberikan kepada desa dengan pendaftaran aktif dan berstatus hadir.');
		}
		if ((bool) $context['state']['is_finished'] || (int) $context['state']['current_round'] !== $roundNumber)
		{
			$db->trans_rollback();

			return ActionResult::fail('Babak ini bukan babak aktif. Muat ulang papan Cerdas Cermat.');
		}
		if ( ! $this->CI->Cerdas_cermat_model->add_points($entryId, $roundNumber, $delta, $judgeId) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok('Poin Babak '.$roundNumber.' berhasil disimpan.');
	}

	/** Catat pengurangan indisipliner dengan alasan pada babak yang sedang berjalan. */
	public function add_cerdas_cermat_penalty(int $judgeId, int $entryId, int $roundNumber, ?float $points, string $reason): ActionResult
	{
		$reason = trim($reason);
		if ($roundNumber < 1 || $roundNumber > 4 || $points === NULL || $points <= 0 || $reason === '' || mb_strlen($reason) > 500)
		{
			return ActionResult::fail('Poin pengurangan harus lebih dari 0 dan alasan wajib diisi (maksimal 500 karakter).');
		}
		$entry = $this->CI->Entry_model->find($entryId);
		if ( ! $entry)
		{
			return ActionResult::fail('Peserta tidak ditemukan.');
		}
		$db = $this->CI->db;
		$db->trans_begin();
		$context = $this->lock_cerdas_cermat($judgeId, (int) $entry['competition_id']);
		if (is_string($context))
		{
			$db->trans_rollback();

			return ActionResult::fail($context);
		}
		$lockedEntry = $db->query(
			'SELECT id FROM entries WHERE id = ? AND competition_id = ? AND status = ? AND attendance_status = ? FOR UPDATE',
			array($entryId, (int) $entry['competition_id'], 'registered', 'present')
		)->row_array();
		if ( ! $lockedEntry)
		{
			$db->trans_rollback();

			return ActionResult::fail('Pengurangan hanya dapat dicatat untuk desa aktif yang telah ditandai hadir.');
		}
		if ((bool) $context['state']['is_finished'] || (int) $context['state']['current_round'] !== $roundNumber)
		{
			$db->trans_rollback();

			return ActionResult::fail('Babak ini bukan babak aktif. Muat ulang papan Cerdas Cermat.');
		}
		if ( ! $this->CI->Penalty_model->add_round($entryId, CerdasCermat::penalty_key($roundNumber), $points, $reason, $judgeId) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok('Pengurangan Babak '.$roundNumber.' berhasil diterapkan.');
	}

	public function advance_cerdas_cermat_round(int $judgeId, int $competitionId, int $roundNumber): ActionResult
	{
		$db = $this->CI->db;
		$db->trans_begin();
		$context = $this->lock_cerdas_cermat($judgeId, $competitionId);
		if (is_string($context))
		{
			$db->trans_rollback();

			return ActionResult::fail($context);
		}
		if ((bool) $context['state']['is_finished'] || (int) $context['state']['current_round'] !== $roundNumber)
		{
			$db->trans_rollback();

			return ActionResult::fail('Babak sudah berubah atau telah selesai. Muat ulang papan Cerdas Cermat.');
		}
		$entries = $db->query(
			'SELECT id FROM entries WHERE competition_id = ? AND status = ? AND attendance_status = ? ORDER BY id FOR UPDATE',
			array($competitionId, 'registered', 'present')
		)->result_array();
		if ($entries === array())
		{
			$db->trans_rollback();

			return ActionResult::fail('Belum ada desa hadir yang dapat diproses.');
		}
		$entryIds = array_map('intval', array_column($entries, 'id'));
		if ( ! $this->CI->Cerdas_cermat_model->save_missing_round_scores($entryIds, $roundNumber, $judgeId))
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$nextRound = CerdasCermat::next_round($roundNumber);
		$finished = $nextRound === NULL;
		if ($finished)
		{
			$nextRound = 4;
		}
		if ( ! $this->CI->Cerdas_cermat_model->set_progress($competitionId, $nextRound, $finished, $judgeId) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok($finished ? 'Cerdas Cermat selesai. Seluruh nilai dan peringkat akhir telah tersimpan.' : 'Babak '.$roundNumber.' tersimpan; lanjut ke Babak '.$nextRound.'.');
	}

	public function back_cerdas_cermat_round(int $judgeId, int $competitionId, int $roundNumber): ActionResult
	{
		$db = $this->CI->db;
		$db->trans_begin();
		$context = $this->lock_cerdas_cermat($judgeId, $competitionId);
		if (is_string($context))
		{
			$db->trans_rollback();

			return ActionResult::fail($context);
		}
		$previousRound = CerdasCermat::previous_round($roundNumber);
		if ((bool) $context['state']['is_finished'] || (int) $context['state']['current_round'] !== $roundNumber || $previousRound === NULL)
		{
			$db->trans_rollback();

			return ActionResult::fail('Tidak dapat kembali dari babak ini. Muat ulang papan Cerdas Cermat.');
		}
		if ( ! $this->CI->Cerdas_cermat_model->set_progress($competitionId, $previousRound, FALSE, $judgeId) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok('Kembali ke Babak '.$previousRound.'.');
	}

	/** @return array{competition:array<string,mixed>,state:array<string,mixed>}|string */
	private function lock_cerdas_cermat(int $judgeId, int $competitionId)
	{
		$db = $this->CI->db;
		$lockedCompetition = $db->query(
			'SELECT id, is_active, results_published FROM competitions WHERE id = ? FOR UPDATE',
			array($competitionId)
		)->row_array();
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $this->CI->Competition_model->is_judge_assigned($competitionId, $judgeId))
		{
			return 'Anda tidak terdaftar sebagai juri lomba ini.';
		}
		$competition = $this->CI->Competition_model->find($competitionId);
		if ( ! $competition || ! $this->CI->Competition_model->is_cerdas_cermat($competition))
		{
			return 'Lomba ini bukan Cerdas Cermat.';
		}
		if ((bool) $lockedCompetition['results_published'])
		{
			return 'Penilaian lomba ini ditutup karena hasil sudah dipublikasikan.';
		}
		if ( ! $this->CI->Cerdas_cermat_model->ensure_progress($competitionId))
		{
			return 'Status babak Cerdas Cermat tidak dapat disiapkan.';
		}
		$state = $db->query(
			'SELECT current_round, is_finished FROM cerdas_cermat_progress WHERE competition_id = ? FOR UPDATE',
			array($competitionId)
		)->row_array();
		if ( ! $state)
		{
			return 'Status babak Cerdas Cermat tidak ditemukan.';
		}

		return array('competition' => $competition, 'state' => $state);
	}

	/**
	 * @param array<int,float> $scores criterion_id => nilai
	 * @param list<int> $expectedCriteria
	 * @param list<array<string,mixed>> $criteria
	 */
	private function score_set_matches_criteria(array $scores, array $expectedCriteria, array $criteria): bool
	{
		$actualCriteria = array_map('intval', array_keys($scores));
		sort($actualCriteria);
		if ($actualCriteria !== $expectedCriteria)
		{
			return FALSE;
		}
		foreach ($criteria as $criterion)
		{
			$value = (float) $scores[(int) $criterion['id']];
			if ($value < 50 || $value > (float) $criterion['max_score'])
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Koreksi nilai satu juri atas satu entry (Super Admin): alasan wajib, tiap perubahan dicatat di audit.
	 *
	 * @param array<int|string,mixed> $raw criterion_id => nilai baru
	 */
	public function edit_set(int $actorId, string $actorName, int $entryId, int $judgeId, array $raw, string $reason): ActionResult
	{
		$reason = trim($reason);
		if ($reason === '' || mb_strlen($reason) > 500)
		{
			return ActionResult::fail('Alasan koreksi wajib diisi (maksimal 500 karakter).');
		}
		$entry = $this->CI->Entry_model->find($entryId);
		$judge = $this->CI->db->select('full_name')->where('id', $judgeId)->get('users')->row_array();
		$current = $this->CI->Score_model->for_entry_judge($entryId, $judgeId);
		if ( ! $entry || ! $judge || $current === array())
		{
			return ActionResult::fail('Nilai tidak ditemukan.');
		}
		$parsed = $this->parse_all(array_map(static fn ($s) => array('id' => $s['criterion_id'], 'name' => $s['criterion_name'], 'max_score' => $s['max_score']), $current), $raw);
		if (is_string($parsed))
		{
			return ActionResult::fail($parsed);
		}

		$changes = array();
		foreach ($current as $s)
		{
			$new = $parsed[(int) $s['criterion_id']];
			if (abs($new - (float) $s['score']) > 0.0001)
			{
				$changes[] = array($s, $new);
			}
		}
		if ($changes === array())
		{
			return ActionResult::fail('Tidak ada perubahan nilai.');
		}

		$db = $this->CI->db;
		$db->trans_begin();
		foreach ($changes as [$s, $new])
		{
			$ok = $this->CI->Score_model->update_value((int) $s['id'], $new)
				&& $this->CI->Audit_model->log(array(
					'action' => 'edit',
					'competition_id' => (int) $entry['competition_id'],
					'competition_name' => $entry['competition_name'],
					'village_name' => $entry['village_name'],
					'entry_label' => $entry['label'],
					'judge_name' => $judge['full_name'],
					'criterion_name' => $s['criterion_name'],
					'old_score' => $s['score'],
					'new_score' => $new,
					'reason' => $reason,
					'changed_by' => $actorId,
					'changed_by_name' => $actorName,
				));
			if ( ! $ok)
			{
				$error = $db->error();
				$db->trans_rollback();

				return ActionResult::fail(DbError::friendly($error));
			}
		}
		$db->trans_commit();

		return ActionResult::ok('Nilai dikoreksi dan dicatat di riwayat.');
	}

	/**
	 * Admin Lomba melengkapi atau mengoreksi nilai seorang juri pada lomba yang ditugaskan.
	 *
	 * @param array<int|string,mixed> $raw criterion_id => nilai baru
	 */
	public function manage_set(int $actorId, string $actorName, int $competitionId, int $entryId, int $judgeId, array $raw, string $reason): ActionResult
	{
		$reason = trim($reason);
		if ($reason === '' || mb_strlen($reason) > 500)
		{
			return ActionResult::fail('Alasan input/koreksi wajib diisi (maksimal 500 karakter).');
		}
		$competition = $this->CI->Competition_model->find($competitionId);
		$entry = $this->CI->Entry_model->find($entryId);
		$judge = $this->CI->db->select('full_name')->where('id', $judgeId)->where('role', \App\Domain\Role::JUDGE)->get('users')->row_array();
		if ( ! $competition || ! $entry || ! $judge
			|| (int) $entry['competition_id'] !== $competitionId
			|| ! $this->CI->Competition_model->is_manager_assigned($competitionId, $actorId)
			|| ! $this->CI->Competition_model->is_judge_assigned($competitionId, $judgeId))
		{
			return ActionResult::fail('Lomba, peserta, atau juri tidak termasuk penugasan Admin Lomba ini.');
		}
		if ( ! $competition['is_active'] || $entry['status'] !== 'registered' || $entry['attendance_status'] !== 'present')
		{
			return ActionResult::fail('Nilai hanya dapat dikelola untuk peserta aktif yang telah ditandai hadir.');
		}
		if ($competition['results_published'])
		{
			return ActionResult::fail('Penilaian lomba ini ditutup karena hasil sudah dipublikasikan.');
		}
		if ($competition['criteria'] === array())
		{
			return ActionResult::fail('Lomba ini belum memiliki kriteria penilaian.');
		}
		$parsed = $this->parse_all($competition['criteria'], $raw);
		if (is_string($parsed))
		{
			return ActionResult::fail($parsed);
		}

		$db = $this->CI->db;
		$db->trans_begin();
		$lockedEntry = $db->query('SELECT status, attendance_status FROM entries WHERE id = ? FOR UPDATE', array($entryId))->row_array();
		$lockedCompetition = $db->query('SELECT results_published FROM competitions WHERE id = ? FOR UPDATE', array($competitionId))->row_array();
		if ( ! $lockedEntry || $lockedEntry['status'] !== 'registered' || $lockedEntry['attendance_status'] !== 'present'
			|| ! $lockedCompetition || (bool) $lockedCompetition['results_published'])
		{
			$db->trans_rollback();

			return ActionResult::fail('Penilaian tidak dapat diubah: peserta belum hadir atau hasil sudah dipublikasikan.');
		}

		$current = $this->CI->Score_model->for_entry_judge($entryId, $judgeId);
		$currentByCriterion = array();
		foreach ($current as $score)
		{
			$currentByCriterion[(int) $score['criterion_id']] = $score;
		}
		$action = $current === array() ? 'manager_input' : 'manager_edit';
		$changes = array();
		foreach ($competition['criteria'] as $criterion)
		{
			$criterionId = (int) $criterion['id'];
			$newValue = $parsed[$criterionId];
			$oldScore = $currentByCriterion[$criterionId] ?? NULL;
			if ($oldScore !== NULL && abs($newValue - (float) $oldScore['score']) < 0.0001)
			{
				continue;
			}
			if ($oldScore === NULL)
			{
				$ok = $this->CI->Score_model->insert_one($entryId, $judgeId, $criterionId, $newValue);
				$oldValue = NULL;
			}
			else
			{
				$ok = $this->CI->Score_model->update_value((int) $oldScore['id'], $newValue);
				$oldValue = $oldScore['score'];
			}
			if ( ! $ok || ! $this->CI->Audit_model->log(array(
				'action' => $action,
				'competition_id' => $competitionId,
				'competition_name' => $competition['name'],
				'village_name' => $entry['village_name'],
				'entry_label' => $entry['label'],
				'judge_name' => $judge['full_name'],
				'criterion_name' => $criterion['name'],
				'old_score' => $oldValue,
				'new_score' => $newValue,
				'reason' => $reason,
				'changed_by' => $actorId,
				'changed_by_name' => $actorName,
			)))
			{
				$error = $db->error();
				$db->trans_rollback();

				return ActionResult::fail(DbError::friendly($error));
			}
			$changes[] = $criterion['name'];
		}
		if ($changes === array())
		{
			$db->trans_rollback();

			return ActionResult::fail('Tidak ada perubahan nilai.');
		}
		$db->trans_commit();

		return ActionResult::ok(($action === 'manager_input' ? 'Nilai juri dilengkapi' : 'Nilai juri dikoreksi').' dan dicatat di riwayat.');
	}

	/** Hapus seluruh input satu juri pada satu entry agar dapat dinilai ulang (Super Admin). */
	public function reset_judge_entry(int $actorId, string $actorName, int $entryId, int $judgeId, string $reason): ActionResult
	{
		$reason = trim($reason);
		if ($reason === '' || mb_strlen($reason) > 500)
		{
			return ActionResult::fail('Alasan reset wajib diisi (maksimal 500 karakter).');
		}
		$entry = $this->CI->Entry_model->find($entryId);
		$judge = $this->CI->db->select('full_name')->where('id', $judgeId)->get('users')->row_array();
		if ( ! $entry || ! $judge)
		{
			return ActionResult::fail('Data tidak ditemukan.');
		}

		$db = $this->CI->db;
		$db->trans_begin();
		if ($this->CI->Score_model->delete_for_entry_judge($entryId, $judgeId) === 0)
		{
			$db->trans_rollback();

			return ActionResult::fail('Juri ini belum menilai peserta tersebut.');
		}
		$ok = $this->CI->Audit_model->log(array(
			'action' => 'reset_judge',
			'competition_id' => (int) $entry['competition_id'],
			'competition_name' => $entry['competition_name'],
			'village_name' => $entry['village_name'],
			'entry_label' => $entry['label'],
			'judge_name' => $judge['full_name'],
			'criterion_name' => NULL,
			'old_score' => NULL,
			'new_score' => NULL,
			'reason' => $reason,
			'changed_by' => $actorId,
			'changed_by_name' => $actorName,
		));
		if ( ! $ok)
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok('Input juri direset; juri dapat menilai ulang.');
	}

	/**
	 * @param list<array{id:int|string,name:string,max_score:float|string}> $criteria
	 * @param array<int|string,mixed> $raw
	 * @return array<int,float>|string peta criterion_id => nilai, atau pesan error
	 */
	private function parse_all(array $criteria, array $raw)
	{
		$scores = array();
		foreach ($criteria as $cr)
		{
			$value = self::parse_score($raw[$cr['id']] ?? NULL);
			if ($value === NULL)
			{
				return 'Nilai "'.$cr['name'].'" harus berupa angka (maks. 2 desimal).';
			}
			if ($value > (float) $cr['max_score'])
			{
				return 'Nilai "'.$cr['name'].'" tidak boleh lebih dari '.(float) $cr['max_score'].'.';
			}
			if ($value < 50)
			{
				return 'Nilai "'.$cr['name'].'" minimal 50.';
			}
			$scores[(int) $cr['id']] = $value;
		}

		return $scores;
	}
}
