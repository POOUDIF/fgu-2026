<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/** Area Juri (/juri/*). */
class Judge_Controller extends Auth_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_roles(Role::JUDGE);
	}

	/** @return array{portal:array<string,mixed>} */
	protected function portal(): array
	{
		return array('portal' => array(
			'label' => 'Juri',
			'identity' => $this->current_user['full_name'],
			'avatar' => 'J',
			'role_shell' => TRUE,
			'judge_shell' => TRUE,
			'home_href' => 'juri',
			'page_title' => 'Halo, '.$this->current_user['full_name'],
			'menu' => array(
				array('label' => 'Ringkasan', 'href' => 'juri', 'icon' => '⌂', 'exact' => TRUE),
				array('label' => 'Lomba Saya', 'href' => 'juri/lomba', 'icon' => '♜'),
				array('label' => 'Penilaian', 'href' => 'juri/penilaian', 'active_on' => 'juri/nilai', 'icon' => '✓'),
				array('label' => 'Penilaian Tersimpan', 'href' => 'juri/riwayat', 'icon' => '▤'),
				array('label' => 'Kontrol Lomba', 'href' => 'juri/kontrol-lomba', 'icon' => '☷'),
				array('label' => 'Kontrol Penilaian', 'href' => 'juri/kontrol-nilai', 'icon' => '◷'),
			),
		));
	}
}
