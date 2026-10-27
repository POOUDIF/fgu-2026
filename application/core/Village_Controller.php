<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/** Area Admin Desa (/desa/*). Wajib role village_admin dan terkait satu desa. */
class Village_Controller extends Auth_Controller
{
	/** @var int|null */
	protected $village_id = NULL;
	/** @var array<string,mixed>|null */
	protected $village = NULL;

	public function __construct()
	{
		parent::__construct();
		$this->require_roles(Role::VILLAGE_ADMIN);

		$this->load->model('Village_model');
		$this->village_id = (int) $this->current_user['village_id'] ?: NULL;
		$this->village = $this->village_id ? $this->Village_model->find($this->village_id) : NULL;
	}

	/** Akun tanpa desa tidak dapat bekerja; tampilkan pesan, bukan error 500. */
	protected function require_village(): bool
	{
		if ($this->village !== NULL)
		{
			return TRUE;
		}
		$this->render('desa/no_village', array('title' => 'Desa belum diatur') + $this->portal());

		return FALSE;
	}

	/** @return array{portal:array<string,mixed>} */
	protected function portal(): array
	{
		return array('portal' => array(
			'label' => 'Admin Desa',
			'identity' => $this->village['name'] ?? 'Desa belum diatur',
			'avatar' => '🏘️',
			'role_shell' => TRUE,
			'home_href' => 'desa',
			'page_title' => 'Kontingen '.($this->village['name'] ?? 'Desa'),
			'menu' => array(
				array('label' => 'Ringkasan', 'href' => 'desa', 'icon' => '⌂', 'exact' => TRUE),
				array('label' => 'Pendaftaran Peserta', 'href' => 'desa/daftar', 'icon' => '♧'),
				array('label' => 'Bazaar Desa', 'href' => 'desa/bazaar', 'icon' => '▤'),
				array('label' => 'Data & Foto Peserta', 'href' => 'desa/peserta', 'icon' => '▣'),
				array('label' => 'Hasil', 'href' => 'desa/hasil', 'icon' => '♜'),
				array('label' => 'Pengumpulan Karya Online', 'href' => 'desa/karya', 'icon' => '↥'),
				array('label' => 'Kontrol Lomba', 'href' => 'desa/kontrol-lomba', 'icon' => '☷'),
				array('label' => 'Kontrol Penilaian', 'href' => 'desa/kontrol-nilai', 'icon' => '✓'),
			),
		));
	}
}
