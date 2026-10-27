<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Pendaftaran bazaar dan rekap panitia. */
class Bazaar_model extends CI_Model
{
	public function village_competition(): ?array
	{
		$this->load->model('Competition_model');

		return $this->Competition_model->find_by_slug('bazaar-desa');
	}

	public function submit_umkm(string $owner, string $brand, int $villageId, string $phone, int $booth): string
	{
		$this->db->trans_begin();
		$event = $this->db->query('SELECT id, umkm_booth_quota, umkm_registration_open FROM events ORDER BY created_at, id LIMIT 1 FOR UPDATE')->row_array();
		if ( ! $event || ! (bool) $event['umkm_registration_open'])
		{
			$this->db->trans_rollback();

			return 'Pendaftaran Bazaar UMKM sedang ditutup.';
		}
		if ($booth < 1 || $booth > (int) $event['umkm_booth_quota'])
		{
			$this->db->trans_rollback();

			return 'Nomor booth UMKM tidak valid atau kuota sudah berubah.';
		}
		$validVillage = $this->db->where('id', $villageId)->count_all_results('villages') > 0;
		if ( ! $validVillage)
		{
			$this->db->trans_rollback();

			return 'Pilih desa yang valid.';
		}
		$ok = $this->db->insert('bazaar_umkm', array(
			'owner_name' => $owner, 'brand_name' => $brand, 'village_id' => $villageId,
			'phone' => $phone, 'booth_number' => $booth,
		));
		if ( ! $ok || ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			log_message('error', 'Pendaftaran Bazaar UMKM gagal: '.$error['message']);

			return 'Nomor booth tersebut sudah digunakan atau pendaftaran gagal.';
		}
		$this->db->trans_commit();

		return '';
	}

	public function set_umkm_registration_open(bool $open): bool
	{
		$this->db->trans_begin();
		$event = $this->db->query('SELECT id FROM events ORDER BY created_at, id LIMIT 1 FOR UPDATE')->row_array();
		if ( ! $event)
		{
			$this->db->trans_rollback();
			log_message('error', 'Status pendaftaran Bazaar UMKM tidak dapat diubah: acara tidak ditemukan.');

			return FALSE;
		}

		$updated = $this->db->where('id', (int) $event['id'])->update('events', array(
			'umkm_registration_open' => $open ? 1 : 0,
		));
		if ( ! $updated || ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			log_message('error', 'Perubahan status pendaftaran Bazaar UMKM gagal: '.($error['message'] ?? 'Database rejected the update.'));

			return FALSE;
		}
		$this->db->trans_commit();

		return TRUE;
	}

	public function submit_village(int $villageId, string $theme, string $phone, int $booth): string
	{
		if ($booth < 1 || $booth > 9)
		{
			return 'Nomor stand Bazaar Desa harus antara 1 sampai 9.';
		}
		$this->db->trans_begin();
		$competition = $this->db->query(
			'SELECT id, is_active, registration_open, results_published FROM competitions WHERE slug = ? FOR UPDATE',
			array('bazaar-desa')
		)->row_array();
		if ( ! $competition || ! $competition['is_active'] || ! $competition['registration_open'] || $competition['results_published'])
		{
			$this->db->trans_rollback();

			return 'Pendaftaran Bazaar Desa sedang ditutup.';
		}
		if ($this->db->where('id', $villageId)->count_all_results('villages') === 0)
		{
			$this->db->trans_rollback();

			return 'Pilih desa yang valid.';
		}
		$ok = $this->db->insert('entries', array(
			'competition_id' => (int) $competition['id'],
			'village_id' => $villageId,
			'source' => 'village',
			'team_name' => $theme,
			'entry_type' => 'team',
			'status' => 'registered',
			'attendance_status' => 'present',
		));
		if ( ! $ok)
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			log_message('error', 'Pembuatan entry Bazaar Desa gagal: '.$error['message']);

			return 'Pendaftaran Bazaar Desa gagal dibuat.';
		}
		$entryId = (int) $this->db->insert_id();
		$ok = $this->db->insert('bazaar_village', array(
			'entry_id' => $entryId, 'village_id' => $villageId,
			'theme' => $theme, 'phone' => $phone, 'booth_number' => $booth,
		));
		if ( ! $ok || ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			log_message('error', 'Pendaftaran Bazaar Desa gagal: '.$error['message']);

			return 'Desa atau nomor stand tersebut sudah terdaftar.';
		}
		$this->db->trans_commit();

		return '';
	}

	public function update_village(int $villageId, string $theme, string $phone, int $booth): string
	{
		if ($booth < 1 || $booth > 9)
		{
			return 'Nomor stand Bazaar Desa harus antara 1 sampai 9.';
		}
		$this->db->trans_begin();
		$competition = $this->db->query(
			'SELECT id, is_active, registration_open, results_published FROM competitions WHERE slug = ? FOR UPDATE',
			array('bazaar-desa')
		)->row_array();
		if ( ! $competition || ! $competition['is_active'] || ! $competition['registration_open'] || $competition['results_published'])
		{
			$this->db->trans_rollback();

			return 'Pendaftaran Bazaar Desa sedang ditutup.';
		}
		$registration = $this->db->query(
			'SELECT b.entry_id FROM bazaar_village b WHERE b.village_id = ? FOR UPDATE',
			array($villageId)
		)->row_array();
		if ( ! $registration)
		{
			$this->db->trans_rollback();

			return 'Pendaftaran Bazaar Desa tidak ditemukan.';
		}
		$entryId = (int) $registration['entry_id'];
		$entry = $this->db->query(
			'SELECT id FROM entries WHERE id = ? AND village_id = ? AND competition_id = ? AND status = ? FOR UPDATE',
			array($entryId, $villageId, (int) $competition['id'], 'registered')
		)->row_array();
		if ( ! $entry)
		{
			$this->db->trans_rollback();

			return 'Pendaftaran Bazaar Desa tidak ditemukan.';
		}
		if ($this->db->where('entry_id', $entryId)->count_all_results('scores') > 0
			|| $this->db->where('entry_id', $entryId)->count_all_results('score_drafts') > 0)
		{
			$this->db->trans_rollback();

			return 'Pendaftaran Bazaar Desa sudah dinilai atau memiliki draft penilaian sehingga tidak dapat diubah.';
		}
		$boothTaken = $this->db->where('booth_number', $booth)->where('entry_id !=', $entryId)->count_all_results('bazaar_village');
		if ($boothTaken > 0)
		{
			$this->db->trans_rollback();

			return 'Nomor stand tersebut sudah dipilih desa lain.';
		}
		$updatedRegistration = $this->db->where('entry_id', $entryId)->update('bazaar_village', array(
			'theme' => $theme,
			'phone' => $phone,
			'booth_number' => $booth,
		));
		$updatedEntry = $this->db->where(array('id' => $entryId, 'village_id' => $villageId))->update('entries', array(
			'team_name' => $theme,
		));
		if ( ! $updatedRegistration || ! $updatedEntry || ! $this->db->trans_status())
		{
			$error = $this->db->error();
			$this->db->trans_rollback();
			log_message('error', 'Perubahan pendaftaran Bazaar Desa gagal: '.($error['message'] ?? 'Database rejected the update.'));

			return 'Pendaftaran Bazaar Desa gagal diperbarui.';
		}
		$this->db->trans_commit();

		return '';
	}

	public function all(): array
	{
		return array(
			'umkm' => $this->db->select('b.*, v.name AS village_name')->from('bazaar_umkm b')->join('villages v', 'v.id = b.village_id')->order_by('b.booth_number')->get()->result_array(),
			'village' => $this->db->select('b.*, v.name AS village_name')->from('bazaar_village b')->join('villages v', 'v.id = b.village_id')->order_by('b.booth_number')->get()->result_array(),
		);
	}

	public function selected_umkm_booths(): array
	{
		return array_map('intval', array_column($this->db->select('booth_number')->get('bazaar_umkm')->result_array(), 'booth_number'));
	}

	public function registered_villages(): array
	{
		return array_map('intval', array_column($this->db->select('village_id')->get('bazaar_village')->result_array(), 'village_id'));
	}

	public function village_registration(int $villageId): ?array
	{
		$row = $this->db->where('village_id', $villageId)->get('bazaar_village')->row_array();

		return $row ?: NULL;
	}

	public function selected_village_booths(): array
	{
		return array_map('intval', array_column($this->db->select('booth_number')->get('bazaar_village')->result_array(), 'booth_number'));
	}
}
