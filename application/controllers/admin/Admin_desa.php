<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;
use App\Support\DbError;

/** /admin/desa — CRUD desa peserta. */
class Admin_desa extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Village_model');
	}

	public function index()
	{
		$this->render('admin/desa', array(
			'title' => 'Daftar Desa',
			'villages' => $this->Village_model->all(),
		) + $this->portal());
	}

	public function save()
	{
		$id = (int) $this->input->post('id');
		$name = trim((string) $this->input->post('name'));
		$pic = trim((string) $this->input->post('pic_name'));

		if ($name === '' || mb_strlen($name) > 100 || mb_strlen($pic) > 100)
		{
			$this->finish(ActionResult::fail('Nama desa wajib diisi (maksimal 100 karakter).'), 'admin/desa');
		}
		if ($id > 0)
		{
			if ($this->Village_model->find($id) === NULL)
			{
				show_404();
			}
			$ok = $this->Village_model->update($id, $name, $pic === '' ? NULL : $pic);
		}
		else
		{
			$ok = $this->Village_model->create($name, $pic === '' ? NULL : $pic) !== NULL;
		}
		$this->finish($ok ? ActionResult::ok('Data desa tersimpan.') : ActionResult::fail(DbError::friendly($this->db->error())), 'admin/desa');
	}

	public function delete(int $id)
	{
		if ($this->Village_model->find($id) === NULL)
		{
			show_404();
		}
		$ok = $this->Village_model->delete($id);
		$this->finish($ok ? ActionResult::ok('Desa dihapus.') : ActionResult::fail('Desa masih memiliki akun atau peserta sehingga tidak dapat dihapus.'), 'admin/desa');
	}
}
