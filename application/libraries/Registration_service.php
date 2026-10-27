<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\RegistrationInput;
use App\Domain\RegistrationWindow;
use App\Support\ActionResult;
use App\Support\DbError;
use App\Support\Url;

/**
 * Pendaftaran peserta lomba oleh Admin Desa dan kiriman karya online dari halaman publik.
 * Semua aturan dievaluasi di sini (UI hanya pre-filter) dan ditulis dalam satu transaksi.
 */
class Registration_service
{
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model(array('Event_model', 'Participant_model', 'Entry_model', 'Village_model'));
	}

	public function registration_open(): bool
	{
		$event = $this->CI->Event_model->current();

		return RegistrationWindow::isOpen($event['registration_opens_at'] ?? NULL, $event['registration_closes_at'] ?? NULL);
	}

	/** Save one or more name-based entries for a revised open-registration competition. */
	public function submit_open(array $competition, int $villageId, array $rows, string $source): ActionResult
	{
		if ( ! $this->registration_open())
		{
			return ActionResult::fail('Pendaftaran sedang tidak dibuka.');
		}
		if ( ! $competition['is_active'] || empty($competition['registration_open']) || $competition['registration_mode'] !== 'open')
		{
			return ActionResult::fail('Pendaftaran lomba ini belum dibuka atau tidak tersedia.');
		}
		if ( ! in_array($source, array('public', 'village'), TRUE) || $this->CI->Village_model->find($villageId) === NULL)
		{
			return ActionResult::fail('Pilih desa yang valid.');
		}
		if ($rows === array() || count($rows) > 30)
		{
			return ActionResult::fail('Isi minimal 1 peserta dan maksimal 30 peserta sekali kirim.');
		}
		if (count($rows) > 1 && stripos($competition['name'], 'mewarna') === FALSE)
		{
			return ActionResult::fail('Lomba ini hanya menerima satu pendaftaran setiap kali kirim.');
		}

		$cleanRows = array();
		$seen = array();
		foreach ($rows as $index => $row)
		{
			$normalized = $this->normalize_open_row($competition, $row, $index + 1);
			if ( ! $normalized->ok)
			{
				return $normalized;
			}
			$cleanRow = $normalized->data['entry'];
			$name = $cleanRow['team_name'];
			$key = RegistrationInput::nameKey($name);
			if (isset($seen[$key]))
			{
				return ActionResult::fail(sprintf('Nama "%s" dicantumkan lebih dari sekali.', $name));
			}
			$seen[$key] = TRUE;
			$cleanRows[] = $cleanRow;
		}

		$db = $this->CI->db;
		$db->trans_begin();
		$lockedCompetition = $db->query('SELECT id, is_active, registration_open, results_published FROM competitions WHERE id = ? FOR UPDATE', array((int) $competition['id']))->row_array();
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $lockedCompetition['registration_open'] || $lockedCompetition['results_published'])
		{
			return $this->rollback('Pendaftaran lomba ini sudah ditutup.');
		}
		$existing = (int) $db->where(array('competition_id' => (int) $competition['id'], 'village_id' => $villageId, 'status' => 'registered'))->count_all_results('entries');
		if ($competition['quota'] !== NULL && $existing + count($cleanRows) > (int) $competition['quota'])
		{
			return $this->rollback('Kuota lomba untuk desa ini sudah terpenuhi.');
		}

		foreach ($cleanRows as $row)
		{
			$duplicate = $db->where(array(
				'competition_id' => (int) $competition['id'],
				'village_id' => $villageId,
				'team_name' => $row['team_name'],
				'status' => 'registered',
			))->count_all_results('entries');
			if ($duplicate > 0)
			{
				return $this->rollback(sprintf('"%s" sudah terdaftar di lomba ini untuk desa tersebut.', $row['team_name']));
			}
			$row['competition_id'] = (int) $competition['id'];
			$row['village_id'] = $villageId;
			$row['source'] = $source;
			if ( ! $db->insert('entries', $row))
			{
				return $this->rollback(DbError::friendly($db->error()));
			}
		}
		$db->trans_commit();

		return ActionResult::ok(count($cleanRows).' pendaftaran berhasil disimpan.');
	}

	/** Update one direct-registration entry owned by a village. */
	public function update_open_entry(int $entryId, int $villageId, array $competition, array $row): ActionResult
	{
		if ( ! $this->registration_open())
		{
			return ActionResult::fail('Pendaftaran sedang tidak dibuka.');
		}
		if (empty($competition['is_active']) || empty($competition['registration_open'])
			|| $competition['registration_mode'] !== 'open')
		{
			return ActionResult::fail('Pendaftaran lomba ini belum dibuka atau sudah ditutup.');
		}
		$normalized = $this->normalize_open_row($competition, $row, 1);
		if ( ! $normalized->ok)
		{
			return $normalized;
		}

		$db = $this->CI->db;
		$db->trans_begin();
		$lockedCompetition = $db->query(
			'SELECT id, is_active, registration_open, results_published FROM competitions WHERE id = ? FOR UPDATE',
			array((int) $competition['id'])
		)->row_array();
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $lockedCompetition['registration_open'] || $lockedCompetition['results_published'])
		{
			return $this->rollback('Pendaftaran lomba ini sudah ditutup.');
		}
		$entry = $db->query(
			'SELECT id FROM entries WHERE id = ? AND village_id = ? AND competition_id = ? AND status = ? FOR UPDATE',
			array($entryId, $villageId, (int) $competition['id'], 'registered')
		)->row_array();
		if ( ! $entry)
		{
			return $this->rollback('Pendaftaran tidak ditemukan.');
		}
		if ($db->where('entry_id', $entryId)->count_all_results('scores') > 0)
		{
			return $this->rollback('Pendaftaran ini sudah dinilai juri sehingga tidak dapat diubah.');
		}
		if ($db->where('entry_id', $entryId)->count_all_results('score_drafts') > 0)
		{
			return $this->rollback('Pendaftaran ini memiliki draft penilaian juri sehingga tidak dapat diubah.');
		}

		$values = $normalized->data['entry'];
		$duplicate = $db->where(array(
			'competition_id' => (int) $competition['id'],
			'village_id' => $villageId,
			'team_name' => $values['team_name'],
			'status' => 'registered',
		))->where('id !=', $entryId)->count_all_results('entries');
		if ($duplicate > 0)
		{
			return $this->rollback(sprintf('"%s" sudah terdaftar di lomba ini untuk desa tersebut.', $values['team_name']));
		}
		if ( ! $this->CI->Entry_model->update_open_village_entry($entryId, $villageId, $values))
		{
			return $this->rollback(DbError::friendly($db->error()));
		}
		if ( ! $db->trans_status())
		{
			return $this->rollback(DbError::friendly($db->error()));
		}
		$db->trans_commit();

		return ActionResult::ok('Data peserta berhasil diperbarui.');
	}

	/**
	 * Simpan (timpa) pendaftaran satu desa pada satu lomba.
	 * Daftar kosong = hapus pendaftaran. Ditolak bila sudah ada nilai juri.
	 *
	 * @param array<string,mixed> $competition hasil Competition_model::find*
	 * @param list<array{name:string,age:string,link:string}> $rows
	 */
	public function save(int $villageId, array $competition, array $rows): ActionResult
	{
		if ( ! $this->registration_open())
		{
			return ActionResult::fail('Pendaftaran sedang tidak dibuka.');
		}
		if ( ! $competition['is_active'] || empty($competition['registration_open']))
		{
			return ActionResult::fail('Pendaftaran lomba ini belum dibuka atau sudah ditutup.');
		}
		$online = $competition['submission_mode'] === 'online';
		if (($error = RegistrationInput::validate($rows, $competition['quota'], $online, $competition['minimum_participants'] ?? NULL)) !== NULL)
		{
			return ActionResult::fail($error);
		}
		foreach ($rows as $r)
		{
			$level = (string) ($r['education_level'] ?? '');
			$gender = (string) ($r['gender'] ?? '');
			$grade = trim((string) ($r['grade'] ?? ''));
			$parent = trim((string) ($r['parent_name'] ?? ''));
			if ( ! in_array($level, array('PAUD', 'TK', 'SD', 'SMP', 'SMA', 'PGM'), TRUE)
				|| ! in_array($gender, array('L', 'P'), TRUE)
				|| ($competition['levels'] !== array() && ! in_array($level, $competition['levels'], TRUE))
				|| ($competition['allowed_genders'] !== array() && ! in_array($gender, $competition['allowed_genders'], TRUE)))
			{
				return ActionResult::fail(sprintf('%s tidak memenuhi jenjang atau ketentuan gender lomba.', $r['name']));
			}
			if (in_array($level, array('SD', 'SMP', 'SMA'), TRUE))
			{
				if ( ! ctype_digit($grade) || (int) $grade < 1 || (int) $grade > 12
					|| ($competition['grade_min'] !== NULL && (int) $grade < (int) $competition['grade_min'])
					|| ($competition['grade_max'] !== NULL && (int) $grade > (int) $competition['grade_max']))
				{
					return ActionResult::fail(sprintf('Kelas %s tidak sesuai dengan ketentuan lomba.', $r['name']));
				}
			}
			elseif ($grade !== '')
			{
				return ActionResult::fail(sprintf('Kelas tidak perlu diisi untuk jenjang %s.', $level));
			}
			if (mb_strlen($parent) < 2 || mb_strlen($parent) > 100)
			{
				return ActionResult::fail(sprintf('Nama orang tua %s wajib diisi (maksimal 100 karakter).', $r['name']));
			}
		}

		$cid = (int) $competition['id'];
		$existing = $this->CI->Entry_model->for_village_competition($villageId, $cid, 'village');
		$oldEntryIds = array_map('intval', array_column($existing, 'id'));
		if ($oldEntryIds !== array() && $this->CI->db->where_in('entry_id', $oldEntryIds)->count_all_results('scores') > 0)
		{
			return ActionResult::fail('Pendaftaran ini sudah dinilai juri sehingga tidak dapat diubah.');
		}
		if ($oldEntryIds !== array() && $this->CI->db->where_in('entry_id', $oldEntryIds)->count_all_results('score_drafts') > 0)
		{
			return ActionResult::fail('Pendaftaran ini memiliki draft penilaian juri sehingga tidak dapat diubah.');
		}
		$clash = $this->CI->Participant_model->names_in_other_competitions($villageId, $cid, array_column($rows, 'name'));
		if ($clash !== array())
		{
			return ActionResult::fail(sprintf('%s sudah terdaftar pada lomba lain. Peserta tidak diperkenankan mengikuti dua lomba berbeda.', $clash[0]));
		}

		$oldParticipantIds = array();
		$photoPathsByName = array();
		$uploadedPhotoPathsByName = array();
		foreach ($existing as $e)
		{
			foreach ($e['members'] as $m)
			{
				$oldParticipantIds[] = (int) $m['id'];
				if ( ! empty($m['photo_path']))
				{
					$photoPathsByName[RegistrationInput::nameKey($m['name'])] = $m['photo_path'];
				}
			}
		}
		foreach ($rows as $row)
		{
			if ( ! empty($row['photo_path']))
			{
				$uploadedPhotoPathsByName[RegistrationInput::nameKey($row['name'])] = $row['photo_path'];
			}
		}
		foreach ($rows as $row)
		{
			$nameKey = RegistrationInput::nameKey($row['name']);
			if ( ! isset($uploadedPhotoPathsByName[$nameKey]) && ! isset($photoPathsByName[$nameKey]))
			{
				return ActionResult::fail(sprintf('Foto peserta %s wajib diunggah (maksimal 2 MB).', $row['name']));
			}
		}

		$db = $this->CI->db;
		$db->trans_begin();
		$lockedCompetition = $db->query('SELECT id, is_active, registration_open, results_published FROM competitions WHERE id = ? FOR UPDATE', array($cid))->row_array();
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $lockedCompetition['registration_open'] || $lockedCompetition['results_published'])
		{
			return $this->rollback('Pendaftaran lomba ini sudah ditutup.');
		}

		$usedPhotoPaths = array();
		foreach ($rows as $row)
		{
			$nameKey = RegistrationInput::nameKey($row['name']);
			$photoPath = $uploadedPhotoPathsByName[$nameKey] ?? ($photoPathsByName[$nameKey] ?? NULL);
			if ($photoPath !== NULL)
			{
				$usedPhotoPaths[] = $photoPath;
			}
		}
		$obsoletePhotoPaths = array_diff(array_values($photoPathsByName), $usedPhotoPaths);

		if ($oldEntryIds !== array())
		{
			$db->where_in('id', $oldEntryIds)->delete('entries');
		}
		$this->CI->Participant_model->delete_many($oldParticipantIds);

		$groups = $competition['is_team'] ? ($rows === array() ? array() : array($rows)) : array_map(static fn ($r) => array($r), $rows);
		foreach ($groups as $group)
		{
			$link = NULL;
			if ($online)
			{
				foreach ($group as $r)
				{
					$u = Url::normalizeHttp($r['link']);
					if (is_string($u))
					{
						$link = $u;
						break;
					}
				}
			}
			if ( ! $db->insert('entries', array('competition_id' => $cid, 'village_id' => $villageId, 'source' => 'village', 'submission_url' => $link)))
			{
				return $this->rollback(DbError::friendly($db->error()));
			}
			$entryId = (int) $db->insert_id();
			foreach ($group as $r)
			{
				$pid = $this->CI->Participant_model->create($villageId, $r['name'], $r['age'] === '' ? NULL : (int) $r['age'], array(
					'parent_name' => $r['parent_name'], 'gender' => $r['gender'], 'education_level' => $r['education_level'],
					'grade' => $r['grade'] === '' ? NULL : (int) $r['grade'],
					'photo_path' => $uploadedPhotoPathsByName[RegistrationInput::nameKey($r['name'])]
						?? ($photoPathsByName[RegistrationInput::nameKey($r['name'])] ?? NULL),
				));
				if ($pid === NULL || ! $db->insert('entry_members', array('entry_id' => $entryId, 'participant_id' => $pid)))
				{
					return $this->rollback(DbError::friendly($db->error()));
				}
			}
		}
		$db->trans_commit();
		$this->delete_obsolete_photos($villageId, $obsoletePhotoPaths);

		return ActionResult::ok($rows === array() ? 'Pendaftaran dihapus.' : 'Pendaftaran peserta tersimpan.');
	}

	/**
	 * Kiriman karya online dari halaman publik (tanpa login).
	 * Kiriman ulang oleh nama yang sama pada lomba yang sama memperbarui tautan.
	 *
	 * @param array<string,mixed> $competition
	 */
	public function public_submit(array $competition, int $villageId, string $rawName, string $rawGender, string $rawLink): ActionResult
	{
		if ( ! $competition['is_active'] || empty($competition['registration_open']) || ! empty($competition['results_published']) || $competition['submission_mode'] !== 'online')
		{
			return ActionResult::fail('Lomba ini tidak menerima pengumpulan karya atau pendaftarannya belum dibuka.');
		}
		if ($this->CI->Village_model->find($villageId) === NULL)
		{
			return ActionResult::fail('Pilih desa yang valid.');
		}
		if ( ! in_array($rawGender, array('L', 'P'), TRUE)
			|| ($competition['allowed_genders'] !== array() && ! in_array($rawGender, $competition['allowed_genders'], TRUE)))
		{
			return ActionResult::fail('Gender peserta tidak sesuai dengan ketentuan lomba.');
		}
		$name = RegistrationInput::cleanName($rawName);
		if ($name === '' || mb_strlen($name) > RegistrationInput::MAX_NAME)
		{
			return ActionResult::fail('Nama peserta / tim wajib diisi (maksimal 100 karakter).');
		}
		$link = Url::normalizeHttp($rawLink);
		if ( ! is_string($link))
		{
			return ActionResult::fail('Link karya wajib berupa URL http:// atau https:// yang valid.');
		}

		$db = $this->CI->db;
		$db->trans_begin();
		$lockedCompetition = $db->query(
			'SELECT id, is_active, registration_open, results_published, submission_mode FROM competitions WHERE id = ? FOR UPDATE',
			array((int) $competition['id'])
		)->row_array();
		if ( ! $lockedCompetition || ! $lockedCompetition['is_active'] || ! $lockedCompetition['registration_open']
			|| $lockedCompetition['results_published'] || $lockedCompetition['submission_mode'] !== 'online')
		{
			return $this->rollback('Pendaftaran lomba ini sudah ditutup.');
		}
		$key = RegistrationInput::nameKey($name);
		$existing = $db->select('p.id, e.id AS entry_id, e.competition_id, e.source')
			->from('participants p')
			->join('entry_members em', 'em.participant_id = p.id', 'left')
			->join('entries e', 'e.id = em.entry_id', 'left')
			->where(array('p.village_id' => $villageId, 'p.name_key' => $key))
			->get()->row_array();

		if ($existing)
		{
			if ((int) ($existing['competition_id'] ?? 0) === (int) $competition['id'])
			{
				if ($db->where('entry_id', (int) $existing['entry_id'])->count_all_results('scores') > 0)
				{
					return $this->rollback('Karya ini sudah dinilai juri sehingga tidak dapat diubah.');
				}
				if ( ! $this->CI->Entry_model->update_submission_url((int) $existing['entry_id'], $link)
					|| ! $this->CI->Participant_model->set_gender((int) $existing['id'], $rawGender)
					|| ! $db->trans_status())
				{
					return $this->rollback(DbError::friendly($db->error()));
				}
				$db->trans_commit();

				return ActionResult::ok('Tautan karya diperbarui.');
			}

			return $this->rollback('Peserta ini sudah terdaftar pada lomba lain. Peserta tidak diperkenankan mengikuti dua lomba berbeda.');
		}

		$pid = $this->CI->Participant_model->create($villageId, $name, NULL, array('gender' => $rawGender));
		if ($pid === NULL)
		{
			return $this->rollback(DbError::friendly($db->error()));
		}
		if ( ! $db->insert('entries', array(
			'competition_id' => (int) $competition['id'], 'village_id' => $villageId,
			'source' => 'public', 'gender' => $rawGender, 'submission_url' => $link,
		)))
		{
			return $this->rollback(DbError::friendly($db->error()));
		}
		$entryId = (int) $db->insert_id();
		if ( ! $db->insert('entry_members', array('entry_id' => $entryId, 'participant_id' => $pid)))
		{
			return $this->rollback(DbError::friendly($db->error()));
		}
		$db->trans_commit();

		return ActionResult::ok('Karya berhasil dikumpulkan. Terima kasih!');
	}

	/** Ubah tautan karya pada entry milik desa (lomba online). */
	public function update_link(int $villageId, int $entryId, mixed $rawUrl): ActionResult
	{
		$entry = $this->CI->Entry_model->find($entryId);
		if ( ! $entry || (int) $entry['village_id'] !== $villageId)
		{
			return ActionResult::fail('Pendaftaran tidak ditemukan.');
		}
		if ($entry['submission_mode'] !== 'online')
		{
			return ActionResult::fail('Lomba ini tidak memakai pengumpulan karya online.');
		}
		$url = Url::normalizeHttp($rawUrl);
		if ($url === FALSE)
		{
			return ActionResult::fail('Tautan karya harus berupa URL http:// atau https:// yang valid.');
		}
		$this->CI->Entry_model->update_submission_url($entryId, $url);

		return ActionResult::ok('Link karya diperbarui.');
	}

	private function normalize_open_row(array $competition, array $row, int $index): ActionResult
	{
		foreach (array('name', 'entry_type', 'gender', 'parent_name', 'ig_username', 'submission_url', 'members_note') as $field)
		{
			if (isset($row[$field]) && ! is_scalar($row[$field]))
			{
				return ActionResult::fail('Data peserta tidak valid.');
			}
		}
		$name = RegistrationInput::cleanName((string) ($row['name'] ?? ''));
		$type = (string) ($row['entry_type'] ?? 'individual');
		$gender = (string) ($row['gender'] ?? '');
		if ( ! in_array($type, array('individual', 'team'), TRUE) || ($type === 'team' && ! $competition['allow_team']))
		{
			return ActionResult::fail(sprintf('Peserta ke-%d: jenis pendaftaran tidak valid untuk lomba ini.', $index));
		}
		if ( ! in_array($gender, array('L', 'P'), TRUE)
			|| ($competition['allowed_genders'] !== array() && ! in_array($gender, $competition['allowed_genders'], TRUE)))
		{
			return ActionResult::fail(sprintf('Peserta ke-%d: gender tidak sesuai dengan ketentuan lomba.', $index));
		}
		if ($name === '' || mb_strlen($name) > RegistrationInput::MAX_NAME)
		{
			return ActionResult::fail(sprintf('Peserta ke-%d: nama wajib diisi (maksimal 100 karakter).', $index));
		}

		$fields = $competition['form_fields'];
		$parent = NULL;
		if (in_array('parent_name', $fields, TRUE))
		{
			$parent = trim((string) ($row['parent_name'] ?? ''));
			if (mb_strlen($parent) < 2 || mb_strlen($parent) > 100)
			{
				return ActionResult::fail(sprintf('Peserta ke-%d: nama orang tua wajib diisi (2–100 karakter).', $index));
			}
		}
		$instagram = NULL;
		if (in_array('ig_username', $fields, TRUE))
		{
			$instagram = ltrim(trim((string) ($row['ig_username'] ?? '')), '@');
			if ( ! preg_match('/^[A-Za-z0-9._]{1,30}$/', $instagram))
			{
				return ActionResult::fail(sprintf('Peserta ke-%d: username Instagram tidak valid.', $index));
			}
		}
		$link = NULL;
		if (in_array('submission_url', $fields, TRUE))
		{
			$link = Url::normalizeHttp((string) ($row['submission_url'] ?? ''));
			if ( ! is_string($link) || mb_strlen($link) > 500)
			{
				return ActionResult::fail(sprintf('Peserta ke-%d: link wajib berupa URL http:// atau https:// yang valid.', $index));
			}
		}
		$note = $type === 'team' ? trim((string) ($row['members_note'] ?? '')) : '';
		if (mb_strlen($note) > 500)
		{
			return ActionResult::fail(sprintf('Peserta ke-%d: daftar anggota maksimal 500 karakter.', $index));
		}

		return ActionResult::ok('', array('entry' => array(
			'team_name' => $name,
			'gender' => $gender,
			'parent_name' => $parent,
			'ig_username' => $instagram,
			'submission_url' => $link,
			'entry_type' => $type,
			'members_note' => $note !== '' ? $note : NULL,
		)));
	}

	private function rollback(string $message): ActionResult
	{
		$this->CI->db->trans_rollback();

		return ActionResult::fail($message);
	}

	/** Hapus foto privat yang tidak lagi terkait peserta setelah penggantian berhasil commit. */
	private function delete_obsolete_photos(int $villageId, array $paths): void
	{
		$root = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR;
		foreach (array_unique($paths) as $storedPath)
		{
			if ( ! preg_match('/^'.(int) $villageId.'\/(?:[0-9]+-[a-f0-9]{16}|upload-[a-f0-9]{32})\.(jpg|jpeg|png|webp)$/i', (string) $storedPath))
			{
				continue;
			}
			$path = $root.$villageId.DIRECTORY_SEPARATOR.basename($storedPath);
			if (is_file($path) && ! unlink($path))
			{
				log_message('error', 'Foto peserta lama gagal dihapus: '.$storedPath);
			}
		}
	}
}
