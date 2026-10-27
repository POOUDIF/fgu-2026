<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller dasar seluruh aplikasi: sesi/autentikasi, header keamanan, render layout,
 * flash message, dan helper respons. Controller turunan: Auth_Controller, Village_Controller,
 * Judge_Controller, Admin_Controller (dimuat otomatis, lihat config.php).
 */
#[\AllowDynamicProperties]
class MY_Controller extends CI_Controller
{
	/** @var array<string,mixed>|null */
	protected $current_user = NULL;

	public function __construct()
	{
		parent::__construct();

		// Samakan zona waktu sesi MySQL dengan aplikasi (DEFAULT CURRENT_TIMESTAMP ikut WIB).
		$this->db->query("SET time_zone = '+07:00'");

		$this->load->library('Auth_service');
		$this->current_user = $this->auth_service->user();
		$this->load->vars('current_user', $this->current_user);

		$this->send_security_headers();
	}

	protected function send_security_headers(): void
	{
		$this->output
			->set_header('X-Content-Type-Options: nosniff')
			->set_header('X-Frame-Options: SAMEORIGIN')
			->set_header('Referrer-Policy: strict-origin-when-cross-origin')
			->set_header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'");
	}

	/**
	 * Render $view di dalam layout. Layout memakai $layout_content dan (opsional) $portal untuk sidebar.
	 *
	 * @param array<string,mixed> $data
	 */
	protected function render(string $view, array $data = array(), string $layout = 'layouts/app'): void
	{
		$data += array('title' => NULL);
		$data['layout_content'] = $this->load->view($view, $data, TRUE);
		$this->load->view($layout, $data);
	}

	protected function flash(string $type, string $message): void
	{
		$this->session->set_flashdata('flash', array('type' => $type, 'message' => $message));
	}

	/** Simpan hasil aksi sebagai flash lalu redirect (pola Post/Redirect/Get). */
	protected function finish(\App\Support\ActionResult $result, string $redirectTo): void
	{
		if ($result->message !== '')
		{
			$this->flash($result->ok ? 'ok' : 'err', $result->message);
		}
		if ( ! $result->ok)
		{
			$this->keep_old_input($result->errors);
		}
		redirect($redirectTo);
	}

	/** Simpan isian form (tanpa field sandi) agar form terisi ulang setelah error validasi. */
	protected function keep_old_input(array $errors = array()): void
	{
		$post = $this->input->post(NULL, FALSE) ?: array(); // tanpa xss_clean: output selalu di-escape di view
		foreach (array_keys($post) as $k)
		{
			if (stripos($k, 'password') !== FALSE || $k === $this->security->get_csrf_token_name())
			{
				unset($post[$k]);
			}
		}
		$this->session->set_flashdata('old', $post);
		$this->session->set_flashdata('errors', $errors);
	}

	protected function require_post(): void
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Metode tidak diizinkan.', 405);
		}
	}

	protected function json($data, int $status = 200): void
	{
		$this->output
			->set_status_header($status)
			->set_header('Cache-Control: no-store')
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	protected function forbidden(string $message = 'Anda tidak memiliki akses ke halaman ini.'): void
	{
		show_error($message, 403, '403 Dilarang');
	}
}

require_once __DIR__.'/Auth_Controller.php';
require_once __DIR__.'/Competition_Manager_Controller.php';
