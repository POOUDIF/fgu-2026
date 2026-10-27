<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;
use App\Support\Format;

/*
 * Helper tampilan. Semua output ke HTML WAJIB lewat e() kecuali markup yang dibuat helper ini.
 */

if ( ! function_exists('e'))
{
	/** Escape untuk konteks HTML (teks & atribut). */
	function e($value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

if ( ! function_exists('csrf_field'))
{
	function csrf_field(): string
	{
		$CI =& get_instance();

		return '<input type="hidden" name="'.e($CI->security->get_csrf_token_name()).'" value="'.e($CI->security->get_csrf_hash()).'">';
	}
}

if ( ! function_exists('old'))
{
	/** Nilai isian form sebelum redirect karena error (flashdata 'old'). */
	function old(string $key, $default = '')
	{
		$CI =& get_instance();
		$old = $CI->session->flashdata('old');

		return (is_array($old) && array_key_exists($key, $old)) ? $old[$key] : $default;
	}
}

if ( ! function_exists('asset'))
{
	/** URL aset statis dengan cache-busting berdasarkan waktu modifikasi berkas. */
	function asset(string $path): string
	{
		$file = FCPATH.'assets/'.ltrim($path, '/');
		$v = is_file($file) ? filemtime($file) : 0;

		return base_url('assets/'.ltrim($path, '/')).'?v='.$v;
	}
}

if ( ! function_exists('fmt_score'))
{
	function fmt_score($n): string
	{
		return Format::score($n);
	}
}

if ( ! function_exists('fmt_date'))
{
	function fmt_date(?string $v): string
	{
		return Format::date($v);
	}
}

if ( ! function_exists('fmt_datetime'))
{
	function fmt_datetime(?string $v): string
	{
		return Format::dateTime($v);
	}
}

if ( ! function_exists('role_label'))
{
	function role_label(?string $r): string
	{
		return Role::label($r);
	}
}

if ( ! function_exists('tag'))
{
	/** @param string $kind ''|yellow|blue|red */
	function tag(string $text, string $kind = ''): string
	{
		return '<span class="tag '.e($kind).'">'.e($text).'</span>';
	}
}

if ( ! function_exists('pill'))
{
	/** Penanda status pada halaman kontrol. @param string $kind ok|warn|bad|info */
	function pill(string $text, string $kind): string
	{
		return '<span class="statusPill '.e($kind).'">'.($kind === 'ok' ? '✓ ' : '').e($text).'</span>';
	}
}

if ( ! function_exists('nav_active'))
{
	function nav_active(string $href, bool $exact = FALSE): bool
	{
		$CI =& get_instance();
		$current = trim($CI->uri->uri_string(), '/');
		$href = trim($href, '/');

		return $exact ? $current === $href : ($current === $href || strpos($current, $href.'/') === 0);
	}
}

if ( ! function_exists('comp_icon'))
{
	/** Ikon lomba (berkas di assets/icons); fallback emoji bila tidak ada. */
	function comp_icon(array $competition, string $class = 'compImg'): string
	{
		$icon = (string) ($competition['icon'] ?? '');
		if ($icon !== '' && is_file(FCPATH.'assets/icons/'.$icon))
		{
			return '<img class="'.e($class).'" src="'.e(asset('icons/'.$icon)).'" alt="" loading="lazy" width="48" height="48">';
		}

		return '<span class="'.e($class).' fallback" aria-hidden="true">🏅</span>';
	}
}

if ( ! function_exists('multiline'))
{
	/** Teks polos multi-baris -> HTML aman (escape dulu, baru <br>). */
	function multiline(?string $text): string
	{
		return nl2br(e($text), FALSE);
	}
}

if ( ! function_exists('progress_pct'))
{
	function progress_pct(int $done, int $total): int
	{
		return $total > 0 ? (int) round(100 * $done / $total) : 0;
	}
}

if ( ! function_exists('quota_label'))
{
	function quota_label(?int $quota): string
	{
		return $quota === NULL ? 'tidak dibatasi' : $quota.' orang';
	}
}

if ( ! function_exists('page_head'))
{
	/**
	 * Kepala halaman portal. $actions_html adalah markup tepercaya yang dibuat pemanggil (sudah di-escape).
	 */
	function page_head(string $title, string $subtitle = '', string $actions_html = ''): string
	{
		return '<div class="pageHead"><div><h1>'.e($title).'</h1>'.($subtitle !== '' ? '<p>'.e($subtitle).'</p>' : '').'</div>'
			.($actions_html !== '' ? '<div class="actions">'.$actions_html.'</div>' : '').'</div>';
	}
}

if ( ! function_exists('json_attr'))
{
	/** Data JSON untuk atribut HTML (data-fill) — aman dari injeksi atribut/skrip. */
	function json_attr($data): string
	{
		return e(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP));
	}
}

if ( ! function_exists('medal'))
{
	function medal(?int $rank): string
	{
		return $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : (string) $rank));
	}
}
