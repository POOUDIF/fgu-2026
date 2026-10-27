<?php
/** Layout publik: navigasi tetap + drawer mobile + footer. */
$siteName = 'FGU 3.0 — Festival Generasi Unggul | Bekasi Barat 2026';
$pageTitle = !empty($title) ? $title.' · FGU 3.0' : $siteName;
$logo = asset('img/logo-fgu.png');
$userName = $current_user ? (string) ($current_user['full_name'] ?? 'Akun') : '';
$userInitial = $userName !== '' ? mb_strtoupper(mb_substr($userName, 0, 1)) : '';
?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Festival Generasi Unggul 3.0 — kompetisi dan pembinaan generasi muda Bekasi Barat 2026.">
	<title><?= e($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
	<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
	<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="publicBody">
<audio data-public-music autoplay loop preload="auto" aria-hidden="true">
	<source src="<?= e(asset('img/JingleFgu.mpeg')) ?>" type="audio/mpeg">
</audio>
<button type="button" class="publicMusicToggle" data-music-toggle aria-label="Putar musik FGU" title="Putar musik FGU"><span data-music-icon aria-hidden="true">▶</span></button>
<?php $this->load->view('partials/toast'); ?>
<nav class="nav" aria-label="Navigasi utama">
	<div class="navin">
		<a class="brand" href="<?= e(site_url('/')) ?>"><img src="<?= e($logo) ?>" alt="Logo FGU"><div>FGU 3.0<small>FESTIVAL GENERASI UNGGUL</small></div></a>
		<div class="navlinks">
			<a href="<?= e(site_url('/')) ?>#top">Beranda</a>
			<details class="navDropdown">
				<summary>Lomba <span class="chev" aria-hidden="true">⌄</span></summary>
				<div class="navDropdownPanel">
					<a href="<?= e(site_url('/')) ?>#kategori">Kategori</a>
					<a href="<?= e(site_url('/')) ?>#lomba">Daftar Lomba</a>
					<a href="https://drive.google.com/drive/folders/1p98OEnlv9d1jnkCaM0RqXKhpxHprS9uo?usp=sharing" target="_blank" rel="noopener noreferrer">Kisi-Kisi Lomba</a>
					<a href="<?= e(site_url('/')) ?>#jadwal">Rundown</a>
					<a href="<?= e(site_url('/')) ?>#juknis">Juklak &amp; Juknis</a>
					<a href="<?= e(site_url('/')) ?>#denah">Denah Lokasi</a>
					<a href="<?= e(site_url('/')) ?>#pengumpulan">Kumpulkan Karya</a>
					<a href="<?= e(site_url('/')) ?>#pemenang">Juara</a>
				</div>
			</details>
			<a href="<?= e(site_url('/')) ?>#bazaar">Bazaar</a>
			<?php if ($current_user): ?>
				<details class="userMenu">
					<summary><span class="avatar" aria-hidden="true"><?= e($userInitial) ?></span><span class="uname"><?= e($userName) ?></span><span class="chev" aria-hidden="true">⌄</span></summary>
					<div class="userMenuPanel">
						<a href="<?= e(site_url('dashboard')) ?>">Dashboard</a>
						<a href="<?= e(site_url('ganti-sandi')) ?>">Ganti Sandi</a>
						<form method="post" action="<?= e(site_url('logout')) ?>"><?= csrf_field() ?><button type="submit">Keluar</button></form>
					</div>
				</details>
			<?php else: ?>
				<a class="btn primary" href="<?= e(site_url('login')) ?>">Login Portal</a>
			<?php endif; ?>
		</div>
		<button type="button" class="mobileMenuBtn" data-drawer-open aria-expanded="false" aria-controls="mobileDrawer" aria-label="Buka menu">⋮</button>
	</div>
</nav>

<?= $layout_content ?>

<footer>
	<div class="container footerin">
		<div class="footerbrand"><img src="<?= e($logo) ?>" alt=""><div><b>FGU 3.0</b><br><small>Festival Generasi Unggul • Bekasi Barat 2026</small></div></div>
		<small>@festivalgenerasiunggul • @jamsirat</small>
	</div>
</footer>

<div class="mobileDrawer" id="mobileDrawer" hidden>
	<div class="mobileDrawerPanel">
		<div class="drawerHead"><b>Menu FGU 3.0</b><button type="button" class="x" data-drawer-close aria-label="Tutup menu">✕</button></div>
		<a href="<?= e(site_url('/')) ?>#top" data-drawer-close>⌂ Beranda</a>
		<details class="mobileNavGroup">
			<summary>Lomba <span class="chev" aria-hidden="true">⌄</span></summary>
			<a href="<?= e(site_url('/')) ?>#kategori" data-drawer-close>Kategori</a>
			<a href="<?= e(site_url('/')) ?>#lomba" data-drawer-close>Daftar Lomba</a>
			<a href="https://drive.google.com/drive/folders/1p98OEnlv9d1jnkCaM0RqXKhpxHprS9uo?usp=sharing" target="_blank" rel="noopener noreferrer">Kisi-Kisi Lomba</a>
			<a href="<?= e(site_url('/')) ?>#jadwal" data-drawer-close>Rundown</a>
			<a href="<?= e(site_url('/')) ?>#juknis" data-drawer-close>Juklak &amp; Juknis</a>
			<a href="<?= e(site_url('/')) ?>#denah" data-drawer-close>Denah Lokasi</a>
			<a href="<?= e(site_url('/')) ?>#pengumpulan" data-drawer-close>Kumpulkan Karya</a>
			<a href="<?= e(site_url('/')) ?>#pemenang" data-drawer-close>Juara</a>
		</details>
		<a href="<?= e(site_url('/')) ?>#bazaar" data-drawer-close>Bazaar</a>
		<?php if ($current_user): ?>
			<a href="<?= e(site_url('dashboard')) ?>" data-drawer-close>Dashboard</a>
			<a href="<?= e(site_url('ganti-sandi')) ?>" data-drawer-close>Ganti Sandi (<?= e($userName) ?>)</a>
			<form method="post" action="<?= e(site_url('logout')) ?>"><?= csrf_field() ?><button type="submit" class="mobileLoginBtn">Keluar</button></form>
		<?php else: ?>
			<a class="mobileLoginBtn" href="<?= e(site_url('login')) ?>" data-drawer-close>🔐 Login Portal</a>
		<?php endif; ?>
	</div>
</div>
</body>
</html>
