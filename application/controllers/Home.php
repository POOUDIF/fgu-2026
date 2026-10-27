<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\RegistrationWindow;

/** Beranda publik + pengumpulan karya online tanpa login. */
class Home extends MY_Controller
{
	public function index()
	{
		$this->load->model(array('Event_model', 'Content_model', 'Competition_model', 'Village_model'));

		$event = $this->Event_model->current();
		$competitions = $this->Competition_model->active_with_details();
		$publicCompetitions = $competitions;
		$open = array_values(array_filter($publicCompetitions, static fn ($c) => $c['registration_open'] && $c['registration_mode'] === 'open'));
		$onlineCount = count(array_filter($publicCompetitions, static fn ($c) => $c['submission_mode'] === 'online'));
		$online = array_values(array_filter($publicCompetitions, static fn ($c) => $c['registration_open'] && $c['submission_mode'] === 'online' && $c['registration_mode'] !== 'open'));
		$this->load->model('Result_model');
		$this->load->model('Bazaar_model');

		$this->render('public/home', array(
			'title' => NULL,
			'event' => $event,
			'site' => $this->Content_model->all(),
			'competitions' => $publicCompetitions,
			'villages' => $this->Village_model->all(),
			'online_count' => $onlineCount,
			'online' => $online,
			'open' => $open,
			'umkm_registration_open' => ! empty($event['umkm_registration_open']),
			'published_winners' => $this->Result_model->published_winners(),
			'rundown' => $this->Event_model->rundown(),
			'umkm_booths' => $this->Bazaar_model->selected_umkm_booths(),
			'registration' => RegistrationWindow::status($event['registration_opens_at'] ?? NULL, $event['registration_closes_at'] ?? NULL),
		), 'layouts/public');
	}

	/** GET /denah — sajikan hanya gambar denah yang disimpan server. */
	public function site_plan()
	{
		$this->load->model('Event_model');
		$event = $this->Event_model->current();
		$stored = $event['site_plan_path'] ?? '';
		if ( ! preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $stored))
		{
			show_404();
		}
		$path = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'site-plan'.DIRECTORY_SEPARATOR.$stored;
		$info = is_file($path) ? @getimagesize($path) : FALSE;
		if ( ! is_array($info) || ! in_array($info['mime'], array('image/jpeg', 'image/png', 'image/webp'), TRUE))
		{
			show_404();
		}
		$content = file_get_contents($path);
		if ($content === FALSE)
		{
			log_message('error', 'Denah acara gagal dibaca.');
			show_error('Denah tidak dapat dibaca.', 500);
		}
		$this->output->set_content_type($info['mime'])->set_header('X-Content-Type-Options: nosniff')->set_output($content);
	}

	/** GET /juklak — sajikan dokumen juklak lokal yang menjadi sumber ketentuan situs. */
	public function guidelines()
	{
		$path = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'FGU 3.0 _ Juklak Juknis Lomba 2026.pdf';
		if ( ! is_file($path))
		{
			show_404();
		}
		$content = file_get_contents($path);
		if ($content === FALSE)
		{
			log_message('error', 'Dokumen juklak dan juknis gagal dibaca.');
			show_error('Dokumen juklak dan juknis tidak dapat dibaca.', 500);
		}
		$disposition = $this->input->get('download') === '1' ? 'attachment' : 'inline';
		$this->output->set_content_type('application/pdf')
			->set_header('Content-Disposition: '.$disposition.'; filename="FGU-3.0-Juklak-Juknis-2026.pdf"')
			->set_header('X-Content-Type-Options: nosniff')
			->set_output($content);
	}

	/** POST /bazaar/umkm — pendaftaran bazaar UMKM tanpa akun. */
	public function submit_bazaar(string $type = '')
	{
		$this->load->library('Rate_limiter');
		$this->load->model('Bazaar_model');
		$back = site_url('/').'#bazaar';
		$honeypot = trim((string) $this->input->post('website'));
		if ($honeypot !== '')
		{
			$this->flash('ok', 'Pendaftaran bazaar berhasil dikirim.');
			redirect($back);
		}
		if ( ! $this->rate_limiter->hit('bazaar:'.$this->input->ip_address(), 6, 600))
		{
			$this->flash('err', 'Terlalu banyak percobaan. Silakan coba lagi beberapa menit.');
			redirect($back);
		}
		$owner = trim((string) $this->input->post('owner_name'));
		$brand = trim((string) $this->input->post('brand_name'));
		$phone = trim((string) $this->input->post('phone'));
		$villageId = $this->positive_int($this->input->post('village_id'));
		$booth = $this->positive_int($this->input->post('booth_number'));
		if ( ! preg_match('/^[0-9+(). -]{8,30}$/', $phone) || $villageId === NULL || $booth === NULL)
		{
			$this->flash('err', 'Lengkapi desa, nomor stand, dan nomor telepon yang valid.');
			redirect($back);
		}
		if ($type === 'umkm')
		{
			if ($owner === '' || $brand === '' || mb_strlen($owner) > 120 || mb_strlen($brand) > 120)
			{
				$this->flash('err', 'Nama pemilik/PJ dan nama brand wajib diisi (maksimal 120 karakter).');
				redirect($back);
			}
			$message = $this->Bazaar_model->submit_umkm($owner, $brand, $villageId, $phone, $booth);
		}
		else
		{
			show_404();
		}
		$this->flash($message === '' ? 'ok' : 'err', $message === '' ? 'Pendaftaran bazaar berhasil dikirim.' : $message);
		redirect($back);
	}

	private function positive_int($value): ?int
	{
		return ($value !== NULL && is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0) ? (int) $value : NULL;
	}

	/** POST /karya — kiriman karya online dari publik (dibatasi laju per IP). */
	public function submit_work()
	{
		$this->load->library(array('Rate_limiter', 'Registration_service'));
		$this->load->model('Competition_model');
		$back = site_url('/').'#pengumpulan';
		if (trim((string) $this->input->post('website')) !== '')
		{
			$this->flash('ok', 'Pendaftaran berhasil dikirim.');
			redirect($back);
		}

		if ( ! $this->rate_limiter->hit('karya:'.$this->input->ip_address(), 10, 600))
		{
			$this->flash('err', 'Terlalu banyak kiriman. Coba lagi beberapa menit lagi.');
			redirect($back);
		}
		$competition = $this->Competition_model->find((int) $this->input->post('competition_id'));
		if ($competition === NULL)
		{
			$this->flash('err', 'Pilih lomba yang valid.');
			redirect($back);
		}
		if ($this->input->post('registration_mode') === 'open')
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
			$result = $this->registration_service->submit_open($competition, (int) $this->input->post('village_id'), $rows, 'public');
		}
		else
		{
			$result = $this->registration_service->public_submit(
				$competition,
				(int) $this->input->post('village_id'),
				(string) $this->input->post('name'),
				(string) $this->input->post('gender'),
				(string) $this->input->post('link')
			);
		}
		$this->flash($result->ok ? 'ok' : 'err', $result->message);
		redirect($back);
	}
}
