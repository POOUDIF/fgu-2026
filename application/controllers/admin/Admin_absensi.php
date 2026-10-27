<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;

/** Absensi dan verifikasi identitas peserta pada hari lomba. */
class Admin_absensi extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Entry_model', 'Competition_model', 'Village_model', 'Participant_model'));
	}

	public function index()
	{
		$competition = $this->positive_int($this->input->get('lomba'));
		$village = $this->positive_int($this->input->get('desa'));
		$status = $this->input->get('status');
		$status = in_array($status, array('pending', 'present', 'absent'), TRUE) ? $status : NULL;
		$name = trim((string) $this->input->get('nama'));
		if (mb_strlen($name) > 100)
		{
			$name = mb_substr($name, 0, 100);
		}
		$this->render('admin/absensi', array(
			'title' => 'Absensi Peserta',
			'entries' => $this->Entry_model->for_attendance($competition, $village, $status, $name),
			'competitions' => $this->Competition_model->active(),
			'villages' => $this->Village_model->all(),
			'f_lomba' => $competition, 'f_desa' => $village, 'f_status' => $status, 'f_nama' => $name,
		) + $this->portal());
	}

	public function mark(int $entryId)
	{
		$this->require_post();
		$attendance = (string) $this->input->post('attendance');
		$ok = $this->Entry_model->mark_attendance($entryId, $attendance, (int) $this->current_user['id']);
		$filters = array();
		$competition = $this->positive_int($this->input->post('lomba'));
		$village = $this->positive_int($this->input->post('desa'));
		$status = $this->input->post('status');
		$nameInput = $this->input->post('nama');
		$name = is_scalar($nameInput) ? trim((string) $nameInput) : '';
		if (mb_strlen($name) > 100)
		{
			$name = mb_substr($name, 0, 100);
		}
		if ($competition !== NULL)
		{
			$filters['lomba'] = $competition;
		}
		if ($village !== NULL)
		{
			$filters['desa'] = $village;
		}
		if (in_array($status, array('pending', 'present', 'absent'), TRUE))
		{
			$filters['status'] = $status;
		}
		if ($name !== '')
		{
			$filters['nama'] = $name;
		}
		$redirectTo = 'admin/absensi'.($filters ? '?'.http_build_query($filters) : '');
		$this->finish(
			$ok ? ActionResult::ok($attendance === 'present' ? 'Peserta sah dan tercatat hadir.' : 'Peserta ditandai tidak hadir; tidak dapat dinilai.')
				: ActionResult::fail('Absensi gagal. Pastikan pendaftaran peserta masih aktif.'),
			$redirectTo
		);
	}

	public function photo(int $participantId)
	{
		$participant = $this->Participant_model->find($participantId);
		if ( ! $participant || empty($participant['photo_path'])
			|| ! preg_match('/^[0-9]+\/(?:[0-9]+-[a-f0-9]{16}|upload-[a-f0-9]{32})\.(jpg|jpeg|png|webp)$/i', $participant['photo_path']))
		{
			show_404();
		}
		$path = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $participant['photo_path']);
		$info = is_file($path) ? @getimagesize($path) : FALSE;
		if ( ! is_array($info) || ! in_array($info['mime'], array('image/jpeg', 'image/png', 'image/webp'), TRUE))
		{
			show_404();
		}
		$content = file_get_contents($path);
		if ($content === FALSE)
		{
			log_message('error', 'Foto absensi gagal dibaca: '.$participantId);
			show_error('Foto tidak dapat dibaca.', 500);
		}
		$this->output->set_content_type($info['mime'])->set_header('Cache-Control: private, no-store')
			->set_header('X-Content-Type-Options: nosniff')->set_output($content);
	}

	private function positive_int($value): ?int
	{
		return ($value !== NULL && is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0) ? (int) $value : NULL;
	}
}
