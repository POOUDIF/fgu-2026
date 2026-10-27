<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\RegistrationInput;

/** /desa/daftar — pendaftaran peserta per lomba (dialog) dengan kuota per desa. */
class Desa_daftar extends Village_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Entry_model'));
		$this->load->library('Registration_service');
	}

	public function index()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$byCompetition = array();
		foreach ($this->Entry_model->for_village($this->village_id) as $e)
		{
			if ($e['source'] === 'village')
			{
				if (trim((string) ($e['team_name'] ?? '')) !== '')
				{
					$byCompetition[(int) $e['competition_id']][] = array(
						'name' => $e['team_name'], 'parent_name' => $e['parent_name'], 'ig_username' => $e['ig_username'],
						'submission_url' => $e['submission_url'], 'entry_type' => $e['entry_type'], 'members_note' => $e['members_note'],
						'gender' => $e['gender'],
					);
					continue;
				}
				foreach ($e['members'] as $m)
				{
					$byCompetition[(int) $e['competition_id']][] = array(
						'id' => $m['id'], 'name' => $m['name'], 'age' => $m['age'], 'link' => $e['submission_url'], 'gender' => $m['gender'],
						'education_level' => $m['education_level'], 'grade' => $m['grade'], 'parent_name' => $m['parent_name'],
						'photo_path' => $m['photo_path'],
					);
				}
			}
		}

		$competitions = $this->Competition_model->active();
		$this->render('desa/daftar', array(
			'title' => 'Daftar Peserta Lomba',
			'village' => $this->village,
			'competitions' => $competitions,
			'registered' => $byCompetition,
			'registration_open' => $this->registration_service->registration_open(),
		) + $this->portal());
	}

	public function save(string $slug)
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$competition = $this->Competition_model->find_by_slug($slug);
		if ($competition === NULL)
		{
			show_404();
		}
		if ($competition['slug'] === 'bazaar-desa')
		{
			redirect('desa/bazaar');
		}
		if ($competition['registration_mode'] === 'open')
		{
			$rows = array();
			$names = (array) $this->input->post('name');
			foreach ($names as $i => $name)
			{
				if (trim((string) $name) === '')
				{
					continue;
				}
				$rows[] = array(
					'name' => $name,
					'gender' => ((array) $this->input->post('gender'))[$i] ?? '',
					'parent_name' => ((array) $this->input->post('parent_name'))[$i] ?? '',
					'ig_username' => ((array) $this->input->post('ig_username'))[$i] ?? '',
					'submission_url' => ((array) $this->input->post('submission_url'))[$i] ?? '',
					'entry_type' => ((array) $this->input->post('entry_type'))[$i] ?? 'individual',
					'members_note' => ((array) $this->input->post('members_note'))[$i] ?? '',
				);
			}
			$result = $this->registration_service->submit_open($competition, $this->village_id, $rows, 'village');
		}
		else
		{
			$names = (array) $this->input->post('name');
			$existingAges = array();
			foreach ($this->Entry_model->for_village_competition($this->village_id, (int) $competition['id'], 'village') as $existingEntry)
			{
				foreach ($existingEntry['members'] as $member)
				{
					$existingAges[RegistrationInput::nameKey($member['name'])] = $member['age'];
				}
			}
			$ages = (array) $this->input->post('age');
			foreach ($names as $index => $name)
			{
				if (!array_key_exists($index, $ages))
				{
					$ages[$index] = $existingAges[RegistrationInput::nameKey((string) $name)] ?? '';
				}
			}
			$photoPaths = $this->upload_registration_photos($names, $uploadError);
			if ($uploadError !== '')
			{
				$this->flash('err', $uploadError);
				redirect('desa/daftar');
			}
			$rows = RegistrationInput::rows(
				$names,
				$ages,
				(array) $this->input->post('link'),
				(array) $this->input->post('gender'),
				(array) $this->input->post('education_level'),
				(array) $this->input->post('grade'),
				(array) $this->input->post('parent_name')
			);
			$rowIndex = 0;
			foreach ($names as $index => $name)
			{
				if (RegistrationInput::cleanName((string) $name) === '')
				{
					continue;
				}
				if (isset($photoPaths[$index]))
				{
					$rows[$rowIndex]['photo_path'] = $photoPaths[$index];
				}
				$rowIndex++;
			}
			$result = $this->registration_service->save($this->village_id, $competition, $rows);
			if ( ! $result->ok)
			{
				$this->delete_staged_photos($photoPaths);
			}
		}
		$this->finish($result, 'desa/daftar');
	}

	/**
	 * Stage per-participant photos before the registration transaction.
	 *
	 * @param array<int|string,mixed> $names
	 * @return array<int|string,string> uploaded paths keyed by form row index
	 */
	private function upload_registration_photos(array $names, ?string &$error): array
	{
		$error = '';
		if ( ! isset($_FILES['photo']) || ! is_array($_FILES['photo']['error'] ?? NULL))
		{
			return array();
		}

		$directory = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR.$this->village_id.DIRECTORY_SEPARATOR;
		if ( ! is_dir($directory) && ! mkdir($directory, 0750, TRUE) && ! is_dir($directory))
		{
			log_message('error', 'Direktori foto peserta tidak dapat dibuat.');
			show_error('Penyimpanan foto tidak tersedia.', 500);
		}

		$this->load->library('upload');
		$paths = array();
		$mimeByExtension = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
		foreach ($_FILES['photo']['error'] as $index => $uploadError)
		{
			if ((int) $uploadError === UPLOAD_ERR_NO_FILE)
			{
				continue;
			}
			if ( ! array_key_exists($index, $names) || trim((string) $names[$index]) === '')
			{
				$error = 'Pilih nama peserta untuk setiap foto yang diunggah.';
				$this->delete_staged_photos($paths);

				return array();
			}
			if ((int) $uploadError === UPLOAD_ERR_INI_SIZE || (int) $uploadError === UPLOAD_ERR_FORM_SIZE
				|| (int) ($_FILES['photo']['size'][$index] ?? 0) > 2 * 1024 * 1024)
			{
				$error = 'Ukuran setiap foto maksimal 2 MB.';
				$this->delete_staged_photos($paths);

				return array();
			}
			if ((int) $uploadError !== UPLOAD_ERR_OK)
			{
				$error = 'Foto peserta gagal diunggah. Silakan coba kembali.';
				$this->delete_staged_photos($paths);

				return array();
			}

			$field = '_participant_photo_upload';
			$_FILES[$field] = array(
				'name' => $_FILES['photo']['name'][$index] ?? '',
				'type' => $_FILES['photo']['type'][$index] ?? '',
				'tmp_name' => $_FILES['photo']['tmp_name'][$index] ?? '',
				'error' => (int) $uploadError,
				'size' => (int) ($_FILES['photo']['size'][$index] ?? 0),
			);
			$config = array(
				'upload_path' => $directory,
				'allowed_types' => 'jpg|jpeg|png|webp',
				'max_size' => 2049,
				'max_width' => 5000,
				'max_height' => 5000,
				'file_name' => 'upload-'.bin2hex(random_bytes(16)),
				'remove_spaces' => TRUE,
				'detect_mime' => TRUE,
				'mod_mime_fix' => TRUE,
			);
			$this->upload->initialize($config, TRUE);
			if ( ! $this->upload->do_upload($field))
			{
				$error = trim(strip_tags($this->upload->display_errors('', '')));
				unset($_FILES[$field]);
				$this->delete_staged_photos($paths);

				return array();
			}

			$uploaded = $this->upload->data();
			unset($_FILES[$field]);
			$extension = strtolower(ltrim($uploaded['file_ext'], '.'));
			$image = @getimagesize($uploaded['full_path']);
			if ( ! $image || ! isset($mimeByExtension[$extension]) || $image['mime'] !== $mimeByExtension[$extension])
			{
				unlink($uploaded['full_path']);
				$error = 'Berkas harus berupa gambar JPG, PNG, atau WebP yang valid.';
				$this->delete_staged_photos($paths);

				return array();
			}

			$paths[$index] = $this->village_id.'/upload-'.substr($uploaded['file_name'], strlen('upload-'));
		}

		return $paths;
	}

	/** @param array<int|string,string> $paths */
	private function delete_staged_photos(array $paths): void
	{
		$root = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR;
		foreach ($paths as $path)
		{
			if ( ! preg_match('/^'.(int) $this->village_id.'\/upload-[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $path))
			{
				continue;
			}
			$absolutePath = $root.$this->village_id.DIRECTORY_SEPARATOR.basename($path);
			if (is_file($absolutePath) && ! unlink($absolutePath))
			{
				log_message('error', 'Foto peserta sementara gagal dihapus: '.$path);
			}
		}
	}
}
