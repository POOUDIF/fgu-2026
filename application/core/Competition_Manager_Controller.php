<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/** Area Admin Lomba: akses hanya ke lomba yang ditugaskan oleh Admin Daerah. */
class Competition_Manager_Controller extends Auth_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_roles(Role::COMPETITION_ADMIN);
		$this->load->model('Competition_model');
	}

	protected function manager_id(): int
	{
		return (int) $this->current_user['id'];
	}

	/** Lomba aktif yang ditugaskan kepada Admin Lomba. */
	protected function assigned_competition(string $slug): array
	{
		$competition = $this->Competition_model->find_by_slug($slug);
		if ($competition === NULL || ! $competition['is_active'] || ! $this->Competition_model->is_manager_assigned((int) $competition['id'], $this->manager_id()))
		{
			show_404();
		}

		return $competition;
	}

	/** @return array{portal:array<string,mixed>} */
	protected function portal(): array
	{
		return array('portal' => array(
			'label' => 'Admin Lomba',
			'identity' => $this->current_user['full_name'],
			'avatar' => '🏆',
			'role_shell' => TRUE,
			'home_href' => 'admin-lomba',
			'page_title' => 'Admin Lomba · '.$this->current_user['full_name'],
			'menu' => array(
				array('label' => 'Ringkasan', 'href' => 'admin-lomba', 'icon' => '⌂', 'exact' => TRUE),
				array('label' => 'Review Penilaian', 'href' => 'admin-lomba/nilai', 'icon' => '✓'),
			),
		));
	}
}
