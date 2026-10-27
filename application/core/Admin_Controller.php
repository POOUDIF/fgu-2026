<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/** Area panitia (/admin/*): Admin Daerah dan Super Admin. */
class Admin_Controller extends Auth_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_roles(array(Role::REGIONAL_ADMIN, Role::SUPER_ADMIN));
	}

	protected function is_super(): bool
	{
		return Role::isSuper($this->current_user['role']);
	}

	/** Aksi khusus Super Admin. */
	protected function require_super(): void
	{
		if ( ! $this->is_super())
		{
			$this->forbidden('Aksi ini hanya untuk Super Admin.');
		}
	}

	/** @return array{portal:array<string,mixed>} */
	protected function portal(): array
	{
		$menu = array(
			array('label' => 'Dashboard Live', 'href' => 'admin', 'icon' => '▥', 'exact' => TRUE),
			array('label' => 'Peserta & Pendaftaran', 'href' => 'admin/peserta', 'icon' => '♧'),
			array('label' => 'Absensi Peserta', 'href' => 'admin/absensi', 'icon' => '✓'),
			array('label' => 'Bazaar', 'href' => 'admin/bazaar', 'icon' => '▤'),
			array('label' => 'Buzzer Cerdas Cermat', 'href' => 'admin/buzzer', 'icon' => '◉'),
			array('label' => 'Hasil & Publikasi', 'href' => 'admin/hasil', 'icon' => '♜'),
		);
		if ($this->is_super())
		{
			$menu[] = array('label' => 'Akun & Juri', 'href' => 'admin/akun', 'icon' => '⚿');
			$menu[] = array('label' => 'Pengaturan', 'href' => 'admin/homepage', 'icon' => '☷');
		}

		return array('portal' => array(
			'label' => Role::isSuper($this->current_user['role']) ? 'Super Admin' : 'Admin Daerah',
			'identity' => Role::label($this->current_user['role']),
			'avatar' => mb_strtoupper(mb_substr((string) ($this->current_user['full_name'] ?? 'A'), 0, 1)),
			'admin_shell' => TRUE,
			'menu' => $menu,
		));
	}
}
