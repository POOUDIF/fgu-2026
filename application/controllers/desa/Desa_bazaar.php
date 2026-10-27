<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Pendaftaran Bazaar Desa oleh Admin Desa untuk desa yang terikat akunnya. */
class Desa_bazaar extends Village_Controller
{
	public function index()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->load->model('Bazaar_model');
		$competition = $this->Bazaar_model->village_competition();
		$registration = $this->Bazaar_model->village_registration($this->village_id);
		$canEdit = $registration !== NULL
			&& $competition !== NULL
			&& $competition['is_active']
			&& $competition['registration_open']
			&& ! $competition['results_published']
			&& $this->db->where('entry_id', (int) $registration['entry_id'])->count_all_results('scores') === 0
			&& $this->db->where('entry_id', (int) $registration['entry_id'])->count_all_results('score_drafts') === 0;
		$this->render('desa/bazaar', array(
			'title' => 'Bazaar Desa',
			'village' => $this->village,
			'registration' => $registration,
			'selected_booths' => $this->Bazaar_model->selected_village_booths(),
			'registration_open' => $competition !== NULL && $competition['is_active'] && $competition['registration_open'] && ! $competition['results_published'],
			'can_edit' => $canEdit,
			'judges' => $competition['judges'] ?? array(),
			'criteria' => $competition['criteria'] ?? array(),
		) + $this->portal());
	}

	public function save()
	{
		$this->require_post();
		if ( ! $this->require_village())
		{
			return;
		}
		$this->load->model('Bazaar_model');
		$back = 'desa/bazaar';
		$registration = $this->Bazaar_model->village_registration($this->village_id);

		$theme = trim((string) $this->input->post('theme'));
		$phone = trim((string) $this->input->post('phone'));
		$booth = filter_var($this->input->post('booth_number'), FILTER_VALIDATE_INT);
		if ($theme === '' || mb_strlen($theme) > 150)
		{
			$this->flash('err', 'Tema bazaar wajib diisi (maksimal 150 karakter).');
			redirect($back);
		}
		if ( ! preg_match('/^[0-9+(). -]{8,30}$/', $phone) || $booth === FALSE || $booth < 1 || $booth > 9)
		{
			$this->flash('err', 'Isi nomor telepon yang valid dan pilih nomor stand 1 sampai 9.');
			redirect($back);
		}

		$message = $registration === NULL
			? $this->Bazaar_model->submit_village($this->village_id, $theme, $phone, $booth)
			: $this->Bazaar_model->update_village($this->village_id, $theme, $phone, $booth);
		$success = $registration === NULL ? 'Pendaftaran Bazaar Desa berhasil dikirim.' : 'Pendaftaran Bazaar Desa berhasil diperbarui.';
		$this->flash($message === '' ? 'ok' : 'err', $message === '' ? $success : $message);
		redirect($back);
	}
}
