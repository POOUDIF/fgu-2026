<div class="loginWrap">
	<div class="loginCard single">
		<div class="loginMain">
			<h2>Ganti kata sandi</h2>
			<?php if ($forced): ?>
				<div class="alert info">Anda memakai sandi sementara. Demi keamanan, ganti kata sandi sebelum melanjutkan.</div>
			<?php endif; ?>
			<form method="post" action="<?= e(site_url('ganti-sandi')) ?>">
				<?= csrf_field() ?>
				<div class="field"><label for="cp">Kata sandi saat ini</label><input id="cp" type="password" name="current_password" required autocomplete="current-password" maxlength="72"></div>
				<div class="field"><label for="np">Kata sandi baru <small class="sub">(min. 8 karakter)</small></label><input id="np" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
				<div class="field"><label for="np2">Ulangi kata sandi baru</label><input id="np2" type="password" name="password_confirm" required minlength="8" maxlength="72" autocomplete="new-password"></div>
				<div class="row">
					<button class="btn primary" type="submit" data-pending-text="Menyimpan…">Simpan</button>
					<?php if (!$forced): ?><a class="btn ghost" href="<?= e(site_url('dashboard')) ?>">Batal</a><?php endif; ?>
				</div>
			</form>
			<?php if ($forced): ?>
				<form method="post" action="<?= e(site_url('logout')) ?>" class="mt"><?= csrf_field() ?><button type="submit" class="btn ghost sm">Keluar</button></form>
			<?php endif; ?>
		</div>
	</div>
</div>
