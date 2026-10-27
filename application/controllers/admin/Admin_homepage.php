<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;
use App\Support\Format;
use App\Domain\RegistrationWindow;

/** /admin/homepage — konten beranda publik dan data acara (jadwal pendaftaran). */
class Admin_homepage extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_super();
		$this->load->model(array('Event_model', 'Content_model'));
	}

	public function index()
	{
		$event = $this->Event_model->current();
		$this->render('admin/homepage', array(
			'title' => 'Pengaturan',
			'event' => $event,
			'registration_status' => $event === NULL
				? NULL
				: RegistrationWindow::status($event['registration_opens_at'] ?? NULL, $event['registration_closes_at'] ?? NULL),
			'site' => $this->Content_model->all(),
			'rundown' => $this->Event_model->rundown(),
		) + $this->portal());
	}

	public function content()
	{
		$values = array();
		foreach (array('tagline', 'about') as $k)
		{
			$v = (string) $this->input->post($k);
			if (mb_strlen($v) > 5000)
			{
				$this->finish(ActionResult::fail('Teks "'.$k.'" maksimal 5000 karakter.'), 'admin/homepage');
			}
			$values[$k] = $v;
		}
		$ok = $this->Content_model->save($values);
		$this->finish($ok ? ActionResult::ok('Homepage diperbarui. (Isian kosong tidak mengubah konten yang ada.)') : ActionResult::fail('Konten gagal disimpan.'), 'admin/homepage');
	}

	public function event()
	{
		$this->require_super();
		$event = $this->Event_model->current();
		if ($event === NULL)
		{
			show_404();
		}
		$name = trim((string) $this->input->post('name'));
		$date = trim((string) $this->input->post('event_date'));
		$venue = trim((string) $this->input->post('venue'));
		$theme = trim((string) $this->input->post('theme'));
		$opens = Format::fromLocalInput($this->input->post('registration_opens_at'));
		$closes = Format::fromLocalInput($this->input->post('registration_closes_at'));

		if ($name === '' || mb_strlen($name) > 150 || mb_strlen($venue) > 190 || mb_strlen($theme) > 190)
		{
			$this->finish(ActionResult::fail('Nama acara wajib diisi (maks. 150 karakter); tempat dan tema maks. 190 karakter.'), 'admin/homepage');
		}
		if ($date !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
		{
			$this->finish(ActionResult::fail('Format tanggal acara tidak valid.'), 'admin/homepage');
		}
		if ($opens !== NULL && $closes !== NULL && $opens >= $closes)
		{
			$this->finish(ActionResult::fail('Waktu buka pendaftaran harus sebelum waktu tutup.'), 'admin/homepage');
		}

		$ok = $this->Event_model->update((int) $event['id'], array(
			'name' => $name,
			'event_date' => $date === '' ? NULL : $date,
			'venue' => $venue === '' ? NULL : $venue,
			'theme' => $theme === '' ? NULL : $theme,
			'registration_opens_at' => $opens,
			'registration_closes_at' => $closes,
		));
		$this->finish($ok ? ActionResult::ok('Data acara disimpan.') : ActionResult::fail('Data acara gagal disimpan.'), 'admin/homepage');
	}

	public function rundown()
	{
		$this->require_post();
		$this->require_super();
		$times = (array) $this->input->post('start_time');
		$details = (array) $this->input->post('detail');
		$locations = (array) $this->input->post('location');
		$rows = array();
		foreach ($times as $i => $time)
		{
			$time = trim((string) $time);
			$detail = trim((string) ($details[$i] ?? ''));
			$location = trim((string) ($locations[$i] ?? ''));
			if ($time === '' && $detail === '' && $location === '')
			{
				continue;
			}
			if ($time === '' || $detail === '' || $location === '' || mb_strlen($time) > 30 || mb_strlen($detail) > 2000 || mb_strlen($location) > 190)
			{
				$this->finish(ActionResult::fail('Setiap baris rundown harus berisi waktu, detail, dan lokasi yang valid.'), 'admin/homepage');
			}
			$rows[] = array('start_time' => $time, 'detail' => $detail, 'location' => $location);
		}
		$this->finish($this->Event_model->save_rundown($rows) ? ActionResult::ok('Rundown disimpan.') : ActionResult::fail('Rundown gagal disimpan.'), 'admin/homepage');
	}

	public function site_plan()
	{
		$this->require_post();
		$this->require_super();
		$event = $this->Event_model->current();
		if ($event === NULL)
		{
			show_404();
		}
		$quota = filter_var($this->input->post('umkm_booth_quota'), FILTER_VALIDATE_INT);
		if ($quota === FALSE || $quota < 1 || $quota > 500)
		{
			$this->finish(ActionResult::fail('Kuota booth UMKM harus antara 1 dan 500.'), 'admin/homepage');
		}
		$newPath = NULL;
		if ( ! empty($_FILES['site_plan']['name']))
		{
			$directory = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'site-plan'.DIRECTORY_SEPARATOR;
			if ( ! is_dir($directory) && ! mkdir($directory, 0750, TRUE) && ! is_dir($directory))
			{
				log_message('error', 'Direktori denah acara tidak dapat dibuat.');
				show_error('Penyimpanan denah tidak tersedia.', 500);
			}
			$this->load->library('upload', array(
				'upload_path' => $directory, 'allowed_types' => 'jpg|jpeg|png|webp',
				'max_size' => 10240, 'max_width' => 10000, 'max_height' => 10000,
				'file_name' => bin2hex(random_bytes(16)), 'detect_mime' => TRUE, 'mod_mime_fix' => TRUE,
			));
			if ( ! $this->upload->do_upload('site_plan'))
			{
				$this->finish(ActionResult::fail(trim(strip_tags($this->upload->display_errors('', '')))), 'admin/homepage');
			}
			$upload = $this->upload->data();
			$info = @getimagesize($upload['full_path']);
			$types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
			$extension = strtolower(ltrim($upload['file_ext'], '.'));
			if ( ! $info || ! isset($types[$extension]) || $info['mime'] !== $types[$extension])
			{
				unlink($upload['full_path']);
				$this->finish(ActionResult::fail('Berkas denah harus berupa gambar JPG, PNG, atau WebP yang valid.'), 'admin/homepage');
			}
			$newPath = $upload['file_name'];
		}
		$data = array('umkm_booth_quota' => $quota);
		if ($newPath !== NULL)
		{
			$data['site_plan_path'] = $newPath;
		}
		if ( ! $this->Event_model->update((int) $event['id'], $data))
		{
			if ($newPath !== NULL)
			{
				@unlink(rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'site-plan'.DIRECTORY_SEPARATOR.$newPath);
			}
			$this->finish(ActionResult::fail('Denah dan kuota gagal disimpan.'), 'admin/homepage');
		}
		if ($newPath !== NULL && ! empty($event['site_plan_path']) && preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $event['site_plan_path']))
		{
			$old = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'site-plan'.DIRECTORY_SEPARATOR.$event['site_plan_path'];
			if (is_file($old) && ! unlink($old))
			{
				log_message('error', 'Denah lama gagal dihapus: '.$event['site_plan_path']);
			}
		}
		$this->finish(ActionResult::ok('Denah dan kuota booth UMKM disimpan.'), 'admin/homepage');
	}
}
