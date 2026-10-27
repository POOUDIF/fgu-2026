<?php
/** Layout portal (Admin Daerah / Admin Desa / Juri). Variabel: $title, $layout_content, $portal. */
use App\Domain\Role;
$pageTitle = (!empty($title) ? $title.' · ' : '').'Portal FGU 3.0';
$logo = asset('img/logo-fgu.png');
$adminShell = !empty($portal['admin_shell']);
$roleShell = !empty($portal['role_shell']);
?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?= e($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
	<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?php foreach ((array) ($extra_css ?? array()) as $css): ?>
	<link rel="stylesheet" href="<?= e(asset($css)) ?>">
<?php endforeach; ?>
	<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach ((array) ($extra_js ?? array()) as $js): ?>
	<script src="<?= e(asset($js)) ?>" defer></script>
<?php endforeach; ?>
</head>
<body<?= ($adminShell || $roleShell) ? ' class="adminPortalBody"' : '' ?>>
<?php $this->load->view('partials/toast'); ?>
<?php if ($adminShell || $roleShell): ?>
	<div class="adminPortal<?= $roleShell ? ' rolePortal' : '' ?><?= !empty($portal['judge_shell']) ? ' judgePortal' : '' ?>" <?= $adminShell ? 'data-admin-shell' : 'data-role-shell' ?>>
		<nav class="nav adminTopNav" aria-label="Navigasi utama">
			<div class="navin">
				<a class="brand" href="<?= e(site_url('/')) ?>"><img src="<?= e($logo) ?>" alt="Logo FGU"><div>FGU 3.0<small>FESTIVAL GENERASI UNGGUL</small></div></a>
				<button type="button" class="adminMenuToggle" data-admin-menu-open aria-controls="adminSidebar" aria-expanded="false" aria-label="Buka menu">⋮</button>
				<div class="navlinks">
					<a href="<?= e(site_url('/')) ?>#top">Beranda</a>
					<a href="<?= e(site_url('/')) ?>#juknis">Ketentuan</a>
					<a href="<?= e(site_url('/')) ?>#lomba">Lomba</a>
					<a href="<?= e(site_url('/')) ?>#pengumpulan">Kumpulkan Karya</a>
					<a href="<?= e(site_url($portal['home_href'] ?? 'admin')) ?>">Dashboard</a>
					<details class="userMenu adminAccount">
						<summary><span class="avatar" aria-hidden="true"><?= e($portal['avatar']) ?></span><span class="uname"><?= e($portal['label']) ?></span><span class="chev" aria-hidden="true">⌄</span></summary>
						<div class="userMenuPanel">
							<div class="accountSummary"><b><?= e($current_user['full_name']) ?></b><small><?= e($current_user['email']) ?></small></div>
							<?php if (!empty($portal['judge_shell'])): ?>
								<?php foreach ($portal['menu'] as $item): ?>
									<a href="<?= e(site_url($item['href'])) ?>"><?= e($item['label']) ?></a>
								<?php endforeach; ?>
							<?php endif; ?>
							<a href="<?= e(site_url('ganti-sandi')) ?>">Ganti sandi</a>
							<form method="post" action="<?= e(site_url('logout')) ?>"><?= csrf_field() ?><button type="submit">Keluar</button></form>
						</div>
					</details>
				</div>
			</div>
		</nav>
		<div class="adminPortalLayout">
			<aside class="adminSidebar" id="adminSidebar" aria-label="<?= e($portal['label']) ?> menu">
				<div class="adminSidebarTop"><span><?= e(mb_strtoupper($portal['label'])) ?></span><button type="button" data-admin-sidebar-toggle aria-label="Sembunyikan menu" title="Sembunyikan menu"><span aria-hidden="true">‹</span></button></div>
				<nav class="adminSideNav">
					<?php foreach ($portal['menu'] as $item): ?>
						<?php $active = nav_active($item['href'], !empty($item['exact'])) || (!empty($item['active_on']) && nav_active($item['active_on'])); ?>
						<a class="<?= $active ? 'active' : '' ?>" href="<?= e(site_url($item['href'])) ?>" title="<?= e($item['label']) ?>"><span class="adminSideIcon" aria-hidden="true"><?= e($item['icon']) ?></span><span class="adminSideLabel"><?= e($item['label']) ?></span></a>
					<?php endforeach; ?>
				</nav>
			</aside>
			<div class="adminSidebarOverlay" data-admin-menu-close></div>
			<main class="adminMain" id="main">
				<?php if (!empty($portal['judge_shell'])): ?><span class="judgeRoleBadge">Juri</span><?php endif; ?>
				<h1><?= e($portal['page_title'] ?? 'Panel Panitia') ?></h1>
				<?= $layout_content ?>
			</main>
		</div>
	</div>
<?php else: ?>
	<div class="app">
		<nav class="appNav">
			<button type="button" class="sideToggle" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu">⋮</button>
			<a class="brand" href="<?= e(site_url('dashboard')) ?>"><img src="<?= e($logo) ?>" alt="Logo FGU"><div>FGU 3.0<small>PORTAL <?= e(mb_strtoupper($portal['label'])) ?></small></div></a>
			<div class="user">
				<div class="who"><b><?= e($portal['label']) ?></b><small><?= e($portal['identity']) ?></small></div>
				<div class="avatar" aria-hidden="true"><?= e($portal['avatar']) ?></div>
				<a class="btn ghost sm" href="<?= e(site_url('ganti-sandi')) ?>">Sandi</a>
				<form method="post" action="<?= e(site_url('logout')) ?>"><?= csrf_field() ?><button type="submit" class="btn ghost">Keluar</button></form>
				<details class="userMenu appAccount">
					<summary aria-label="Menu akun"><span aria-hidden="true">⋮</span></summary>
					<div class="userMenuPanel">
						<div class="accountSummary"><b><?= e($current_user['full_name']) ?></b><small><?= e($current_user['email']) ?></small></div>
						<a href="<?= e(site_url('ganti-sandi')) ?>">Ganti sandi</a>
						<form method="post" action="<?= e(site_url('logout')) ?>"><?= csrf_field() ?><button type="submit">Keluar</button></form>
					</div>
				</details>
			</div>
		</nav>
		<div class="appBody">
			<aside class="sidebar" id="sidebar" aria-label="Menu portal">
				<?php foreach ($portal['menu'] as $item): ?>
					<?php if (isset($item['section'])): ?>
						<div class="sideTitle"><?= e($item['section']) ?></div>
					<?php else: ?>
						<a class="sideBtn<?= nav_active($item['href'], !empty($item['exact'])) ? ' active' : '' ?>" href="<?= e(site_url($item['href'])) ?>"><?= e($item['label']) ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
				<div class="sideTitle">Lainnya</div>
				<a class="sideBtn" href="<?= e(site_url('/')) ?>">🌐 Lihat Website Publik</a>
			</aside>
			<div class="sidebarOverlay" data-sidebar-close></div>
			<main class="main" id="main"><?= $layout_content ?></main>
		</div>
	</div>
<?php endif; ?>
</body>
</html>
