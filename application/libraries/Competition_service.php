<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;
use App\Support\DbError;
use App\Support\Slug;

/**
 * Pengelolaan lomba oleh admin: data lomba, kriteria penilaian, ketentuan, dan penugasan juri.
 */
class Competition_service
{
	private const MAX_CRITERIA = 30;
	private const MAX_RULES = 40;

	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model(array('Competition_model', 'User_model'));
	}

	/** @return list<string> nama berkas ikon yang tersedia (public/assets/icons) */
	public static function available_icons(): array
	{
		$out = array();
		foreach (glob(FCPATH.'assets/icons/*.png') ?: array() as $file)
		{
			$name = basename($file);
			if ($name !== 'logo-fgu.png')
			{
				$out[] = $name;
			}
		}
		sort($out);

		return $out;
	}

	/**
	 * @param array<string,mixed> $in name, cluster, age_label, type_label, participation_type, quota, submission_mode,
	 *                                place, icon, winner_count, rules (teks, satu per baris), criteria[], judges[]
	 */
	public function save(?int $id, array $in): ActionResult
	{
		$name = trim((string) ($in['name'] ?? ''));
		$age = trim((string) ($in['age_label'] ?? ''));
		if ($name === '' || mb_strlen($name) > 150)
		{
			return ActionResult::fail('Nama lomba wajib diisi (maksimal 150 karakter).');
		}
		if ($age === '' || mb_strlen($age) > 150)
		{
			return ActionResult::fail('Kategori usia wajib diisi (maksimal 150 karakter).');
		}
		$participation = (string) ($in['participation_type'] ?? 'individual');
		$submission = (string) ($in['submission_mode'] ?? 'offline');
		if ( ! in_array($participation, array('individual', 'team'), TRUE) || ! in_array($submission, array('offline', 'online'), TRUE))
		{
			return ActionResult::fail('Jenis peserta atau metode pengumpulan tidak valid.');
		}
		$genderOption = (string) ($in['gender_option'] ?? 'both');
		if ( ! in_array($genderOption, array('both', 'male', 'female'), TRUE))
		{
			return ActionResult::fail('Ketentuan gender lomba tidak valid.');
		}
		$allowedGenders = $genderOption === 'male' ? array('L') : ($genderOption === 'female' ? array('P') : array('L', 'P'));

		$quotaRaw = trim((string) ($in['quota'] ?? ''));
		$quota = NULL;
		if ($quotaRaw !== '')
		{
			if ( ! ctype_digit($quotaRaw) || (int) $quotaRaw < 1 || (int) $quotaRaw > 1000)
			{
				return ActionResult::fail('Kuota harus berupa angka 1–1000 (kosongkan bila tidak dibatasi).');
			}
			$quota = (int) $quotaRaw;
		}
		$minimumRaw = trim((string) ($in['minimum_participants'] ?? ''));
		$minimumParticipants = NULL;
		if ($minimumRaw !== '')
		{
			if ( ! ctype_digit($minimumRaw) || (int) $minimumRaw < 1 || (int) $minimumRaw > 1000)
			{
				return ActionResult::fail('Jumlah minimal peserta harus berupa angka 1–1000.');
			}
			$minimumParticipants = (int) $minimumRaw;
			if ($quota === NULL || $minimumParticipants > $quota)
			{
				return ActionResult::fail('Jumlah minimal peserta harus memiliki kuota maksimal dan tidak boleh melebihi kuota tersebut.');
			}
		}
		$winners = (int) ($in['winner_count'] ?? 3);
		if ($winners < 1 || $winners > 20)
		{
			return ActionResult::fail('Jumlah pemenang harus 1–20.');
		}

		$icon = trim((string) ($in['icon'] ?? ''));
		if ($icon !== '' && ! in_array($icon, self::available_icons(), TRUE))
		{
			return ActionResult::fail('Ikon tidak ditemukan.');
		}

		$criteria = array_values(array_filter(array_map(static fn ($c) => trim((string) $c), (array) ($in['criteria'] ?? array())), static fn ($c) => $c !== ''));
		if ($criteria === array() || count($criteria) > self::MAX_CRITERIA)
		{
			return ActionResult::fail('Isi minimal 1 kriteria penilaian (maksimal '.self::MAX_CRITERIA.').');
		}
		foreach ($criteria as $c)
		{
			if (mb_strlen($c) > 190)
			{
				return ActionResult::fail('Nama kriteria maksimal 190 karakter.');
			}
		}
		$rules = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($in['rules'] ?? '')) ?: array()), static fn ($r) => $r !== ''));
		if (count($rules) > self::MAX_RULES)
		{
			return ActionResult::fail('Ketentuan maksimal '.self::MAX_RULES.' butir.');
		}
		foreach ($rules as $r)
		{
			if (mb_strlen($r) > 600)
			{
				return ActionResult::fail('Satu butir ketentuan maksimal 600 karakter.');
			}
		}

		$judgeIds = array_values(array_unique(array_map('intval', (array) ($in['judges'] ?? array()))));
		$validJudges = array_map('intval', array_column($this->CI->User_model->judges(), 'id'));
		if (array_diff($judgeIds, $validJudges) !== array())
		{
			return ActionResult::fail('Ada juri yang tidak valid.');
		}

		$m = $this->CI->Competition_model;
		$row = array(
			'name' => $name,
			'cluster' => trim((string) ($in['cluster'] ?? '')) ?: NULL,
			'age_label' => $age,
			'type_label' => trim((string) ($in['type_label'] ?? '')) ?: NULL,
			'participation_type' => $participation,
			'quota' => $quota,
			'minimum_participants' => $minimumParticipants,
			'submission_mode' => $submission,
			'place' => trim((string) ($in['place'] ?? '')) ?: NULL,
			'icon' => $icon !== '' ? $icon : NULL,
			'winner_count' => $winners,
			'allowed_genders' => json_encode($allowedGenders, JSON_UNESCAPED_UNICODE),
			'rules' => json_encode($rules, JSON_UNESCAPED_UNICODE),
		);

		$db = $this->CI->db;
		$db->trans_begin();
		if ($id === NULL)
		{
			$row['slug'] = Slug::unique(Slug::make($name), $m->taken_slugs());
			$row['sort_order'] = (int) $db->select_max('sort_order', 'm')->get('competitions')->row()->m + 1;
			if ( ! $db->insert('competitions', $row))
			{
				return $this->fail(DbError::friendly($db->error()));
			}
			$id = (int) $db->insert_id();
		}
		else
		{
			if ($m->find($id) === NULL)
			{
				$db->trans_rollback();

				return ActionResult::fail('Lomba tidak ditemukan.');
			}
			if ( ! $db->where('id', $id)->update('competitions', $row))
			{
				return $this->fail(DbError::friendly($db->error()));
			}
		}

		if (($error = $this->sync_criteria($id, $criteria)) !== NULL)
		{
			return $this->fail($error);
		}
		$this->sync_judges($id, $judgeIds);

		if ( ! $db->trans_status())
		{
			return $this->fail(DbError::friendly($db->error()));
		}
		$db->trans_commit();

		return ActionResult::ok('Data lomba tersimpan.', array('id' => $id));
	}

	public function delete(int $id): ActionResult
	{
		$c = $this->CI->Competition_model->find($id);
		if ($c === NULL)
		{
			return ActionResult::fail('Lomba tidak ditemukan.');
		}
		if ($c['slug'] === 'bazaar-desa')
		{
			return ActionResult::fail('Lomba penilaian Bazaar Desa tidak dapat dihapus.');
		}
		if ($this->CI->db->where('competition_id', $id)->count_all_results('entries') > 0)
		{
			return ActionResult::fail('Lomba sudah memiliki pendaftaran dan tidak dapat dihapus. Nonaktifkan atau hapus pendaftarannya terlebih dahulu.');
		}
		if ( ! $this->CI->db->where('id', $id)->delete('competitions'))
		{
			return ActionResult::fail(DbError::friendly($this->CI->db->error(), 'Lomba tidak dapat dihapus.'));
		}

		return ActionResult::ok('Lomba dihapus.');
	}

	public function set_registration_open(int $competitionId, bool $open): ActionResult
	{
		$db = $this->CI->db;
		$db->trans_begin();
		$competition = $db->query(
			'SELECT id, results_published FROM competitions WHERE id = ? FOR UPDATE',
			array($competitionId)
		)->row_array();
		if ( ! $competition)
		{
			$db->trans_rollback();

			return ActionResult::fail('Lomba tidak ditemukan.');
		}
		if ($open && (bool) $competition['results_published'])
		{
			$db->trans_rollback();

			return ActionResult::fail('Pendaftaran tidak dapat dibuka setelah hasil lomba dipublikasikan.');
		}
		if ( ! $this->CI->Competition_model->set_registration_open($competitionId, $open) || ! $db->trans_status())
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok($open ? 'Pendaftaran lomba dibuka.' : 'Pendaftaran lomba ditutup.');
	}

	/**
	 * Sinkron kriteria berdasarkan urutan: ubah nama yang ada, tambah yang baru, hapus kelebihan
	 * (kriteria yang sudah dipakai nilai tidak boleh dihapus).
	 *
	 * @param list<string> $names
	 */
	private function sync_criteria(int $competitionId, array $names): ?string
	{
		$db = $this->CI->db;
		$existing = $this->CI->Competition_model->criteria($competitionId);

		foreach ($names as $i => $name)
		{
			if (isset($existing[$i]))
			{
				$db->where('id', $existing[$i]['id'])->update('criteria', array('name' => $name, 'sort_order' => $i + 1));
			}
			else
			{
				$db->insert('criteria', array('competition_id' => $competitionId, 'name' => $name, 'max_score' => 100, 'sort_order' => $i + 1));
			}
		}
		foreach (array_slice($existing, count($names)) as $extra)
		{
			if ($db->where('criterion_id', $extra['id'])->count_all_results('scores') > 0)
			{
				return 'Kriteria "'.$extra['name'].'" sudah dipakai penilaian sehingga tidak dapat dihapus.';
			}
			if ($db->where('criterion_id', $extra['id'])->count_all_results('score_drafts') > 0)
			{
				return 'Kriteria "'.$extra['name'].'" dipakai draft penilaian sehingga tidak dapat dihapus.';
			}
			$db->where('id', $extra['id'])->delete('criteria');
		}

		return NULL;
	}

	/** @param list<int> $judgeIds */
	private function sync_judges(int $competitionId, array $judgeIds): void
	{
		$db = $this->CI->db;
		$current = $this->CI->Competition_model->judge_ids($competitionId);
		foreach (array_diff($judgeIds, $current) as $jid)
		{
			$db->insert('competition_judges', array('competition_id' => $competitionId, 'judge_id' => $jid));
		}
		$revoke = array_diff($current, $judgeIds);
		if ($revoke !== array())
		{
			$db->where('competition_id', $competitionId)->where_in('judge_id', array_values($revoke))->delete('competition_judges');
		}
	}

	private function fail(string $message): ActionResult
	{
		$this->CI->db->trans_rollback();

		return ActionResult::fail($message);
	}
}
