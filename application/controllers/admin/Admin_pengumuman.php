<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;

/** /admin/pengumuman — pengumuman di beranda publik. */
class Admin_pengumuman extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Content_model');
	}

	public function index()
	{
		$this->render('admin/pengumuman', array(
			'title' => 'Pengumuman',
			'site' => $this->Content_model->all(),
		) + $this->portal());
	}

	public function save()
	{
		$text = trim((string) $this->input->post('announcement'));
		if ($text === '' || mb_strlen($text) > 5000)
		{
			$this->finish(ActionResult::fail('Isi pengumuman wajib diisi (maksimal 5000 karakter).'), 'admin/pengumuman');
		}
		$ok = $this->Content_model->save(array('announcement' => $text));
		$this->finish($ok ? ActionResult::ok('Pengumuman dipublikasikan.') : ActionResult::fail('Pengumuman gagal disimpan.'), 'admin/pengumuman');
	}
}
