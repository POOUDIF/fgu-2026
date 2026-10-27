<div class="loginWrap">
	<div class="loginCard">
		<div class="loginSide">
			<img src="<?= e(asset('img/logo-fgu.png')) ?>" alt="Logo FGU">
			<h1>FGU 3.0</h1>
			<p>Portal Festival Generasi Unggul Bekasi Barat 2026 untuk pendaftaran desa, pengumpulan karya, penilaian juri, dan rekap pemenang.</p>
			<a class="btn white" href="<?= e(site_url('/')) ?>">← Kembali ke halaman depan</a>
		</div>
		<div class="loginMain">
			<h2>Masuk ke Portal</h2>
			<p class="sub">Gunakan akun yang diberikan panitia (Admin Daerah, Admin Desa, atau Juri).</p>
			<form method="post" action="<?= e(site_url('login')) ?>" autocomplete="on">
				<?= csrf_field() ?>
				<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username" maxlength="190" placeholder="contoh: desa@fgu.id"></div>
				<div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" required autocomplete="current-password" maxlength="72"></div>
				<button class="btn primary block" type="submit" data-pending-text="Memproses…">Login</button>
			</form>
			<div class="demo">Belum punya akun? Hubungi panitia FGU 3.0 — akun dibuat oleh Admin Daerah dan tidak ada pendaftaran mandiri.</div>
		</div>
	</div>
</div>
