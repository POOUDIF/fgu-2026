<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Data peserta dan foto privat untuk Admin Desa. */
class Desa_peserta extends Village_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Entry_model', 'Participant_model'));
	}

	public function index()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->render('desa/peserta', array(
			'title' => 'Data Peserta',
			'entries' => $this->Entry_model->for_village($this->village_id),
			'village' => $this->village,
		) + $this->portal());
	}

	public function edit_open(int $entryId)
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$entry = $this->Entry_model->find_for_village($entryId, $this->village_id);
		if ( ! $entry || $entry['registration_mode'] !== 'open')
		{
			show_404();
		}
		$competition = $this->Competition_model->find((int) $entry['competition_id']);
		if ($competition === NULL)
		{
			show_404();
		}
		$this->load->library('Registration_service');
		$canEdit = $this->registration_service->registration_open()
			&& $competition['is_active']
			&& $competition['registration_open']
			&& ! $competition['results_published']
			&& $entry['status'] === 'registered'
			&& $this->db->where('entry_id', $entryId)->count_all_results('scores') === 0
			&& $this->db->where('entry_id', $entryId)->count_all_results('score_drafts') === 0;
		$this->render('desa/peserta_edit_open', array(
			'title' => 'Ubah Data Peserta',
			'entry' => $entry,
			'competition' => $competition,
			'village' => $this->village,
			'can_edit' => $canEdit,
		) + $this->portal());
	}

	public function save_open(int $entryId)
	{
		$this->require_post();
		if ( ! $this->require_village())
		{
			return;
		}
		$entry = $this->Entry_model->find_for_village($entryId, $this->village_id);
		if ( ! $entry || $entry['registration_mode'] !== 'open')
		{
			show_404();
		}
		$competition = $this->Competition_model->find((int) $entry['competition_id']);
		if ($competition === NULL)
		{
			show_404();
		}
		$this->load->library('Registration_service');
		$result = $this->registration_service->update_open_entry($entryId, $this->village_id, $competition, array(
			'name' => $this->input->post('name'),
			'gender' => $this->input->post('gender'),
			'parent_name' => $this->input->post('parent_name'),
			'ig_username' => $this->input->post('ig_username'),
			'submission_url' => $this->input->post('submission_url'),
			'entry_type' => $this->input->post('entry_type'),
			'members_note' => $this->input->post('members_note'),
		));
		$this->finish($result, 'desa/peserta');
	}

	public function upload_photo(int $participantId)
	{
		$this->require_post();
		if ( ! $this->require_village())
		{
			return;
		}
		$participant = $this->Participant_model->find_for_village($participantId, $this->village_id);
		if ($participant === NULL)
		{
			show_404();
		}
		if (empty($_FILES['photo']['name']))
		{
			$this->flash('err', 'Pilih foto peserta terlebih dahulu.');
			redirect('desa/peserta');
		}
		if ((int) ($_FILES['photo']['size'] ?? 0) > 2 * 1024 * 1024)
		{
			$this->flash('err', 'Ukuran foto peserta maksimal 2 MB.');
			redirect('desa/peserta');
		}

		$directory = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR.$this->village_id.DIRECTORY_SEPARATOR;
		if ( ! is_dir($directory) && ! mkdir($directory, 0750, TRUE) && ! is_dir($directory))
		{
			log_message('error', 'Direktori foto peserta tidak dapat dibuat.');
			show_error('Penyimpanan foto tidak tersedia.', 500);
		}

		$this->load->library('upload', array(
			'upload_path' => $directory,
			'allowed_types' => 'jpg|jpeg|png|webp',
			'max_size' => 2049,
			'max_width' => 5000,
			'max_height' => 5000,
			'file_name' => $participantId.'-'.bin2hex(random_bytes(8)),
			'remove_spaces' => TRUE,
			'detect_mime' => TRUE,
			'mod_mime_fix' => TRUE,
		));
		if ( ! $this->upload->do_upload('photo'))
		{
			$this->flash('err', trim(strip_tags($this->upload->display_errors('', ''))));
			redirect('desa/peserta');
		}

		$uploaded = $this->upload->data();
		$absolutePath = $uploaded['full_path'];
		$info = @getimagesize($absolutePath);
		$mimeByExtension = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
		$extension = strtolower(ltrim($uploaded['file_ext'], '.'));
		$path = $this->village_id.'/'.$uploaded['file_name'];
		if ( ! $info || ! isset($mimeByExtension[$extension]) || $info['mime'] !== $mimeByExtension[$extension])
		{
			unlink($absolutePath);
			$this->flash('err', 'Berkas harus berupa gambar JPG, PNG, atau WebP yang valid.');
			redirect('desa/peserta');
		}

		if ( ! $this->Participant_model->set_photo_path($participantId, $this->village_id, $path))
		{
			unlink($absolutePath);
			log_message('error', 'Path foto peserta gagal disimpan: '.$this->db->error()['message']);
			$this->flash('err', 'Foto gagal disimpan.');
			redirect('desa/peserta');
		}

		$this->delete_stored_photo($participant['photo_path'] ?? NULL);
		$this->flash('ok', 'Foto peserta berhasil diperbarui.');
		redirect('desa/peserta');
	}

	public function photo(int $participantId)
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$participant = $this->Participant_model->find_for_village($participantId, $this->village_id);
		if ( ! $participant || empty($participant['photo_path']))
		{
			show_404();
		}

		if ( ! preg_match('/^'.(int) $this->village_id.'\/(?:[0-9]+-[a-f0-9]{16}|upload-[a-f0-9]{32})\.(jpg|jpeg|png|webp)$/i', $participant['photo_path']))
		{
			show_404();
		}
		$filename = basename($participant['photo_path']);
		$path = $this->photo_directory().$this->village_id.DIRECTORY_SEPARATOR.$filename;
		$info = is_file($path) ? @getimagesize($path) : FALSE;
		$mime = is_array($info) ? $info['mime'] : '';
		if ( ! in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), TRUE))
		{
			show_404();
		}
		$content = file_get_contents($path);
		if ($content === FALSE)
		{
			log_message('error', 'Foto peserta gagal dibaca: '.$participantId);
			show_error('Foto tidak dapat dibaca.', 500);
		}

		$this->output->set_content_type($mime)
			->set_header('Cache-Control: private, no-store')
			->set_header('X-Content-Type-Options: nosniff')
			->set_output($content);
	}

	private function photo_directory(): string
	{
		return rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR;
	}

	private function delete_stored_photo(?string $storedPath): void
	{
		if ($storedPath === NULL || ! preg_match('/^'.(int) $this->village_id.'\/(?:[0-9]+-[a-f0-9]{16}|upload-[a-f0-9]{32})\.(jpg|jpeg|png|webp)$/i', $storedPath))
		{
			return;
		}
		$path = $this->photo_directory().$this->village_id.DIRECTORY_SEPARATOR.basename($storedPath);
		if (is_file($path) && ! unlink($path))
		{
			log_message('error', 'Foto lama peserta gagal dihapus: '.$storedPath);
		}
	}
}
