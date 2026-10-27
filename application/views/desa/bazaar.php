<?php /** @var array $village; @var array|null $registration; @var list<int> $selected_booths; @var bool $registration_open; @var list $judges, $criteria */ ?>
<?= page_head('Bazaar Desa', 'Kelola pendaftaran Bazaar Desa untuk '.$village['name'].'.') ?>

<div class="card sectionCard">
	<div class="between"><h2>Penilaian Bazaar Desa</h2><?= tag($registration_open ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup', $registration_open ? '' : 'yellow') ?></div>
	<p class="sub">Penilaian dilakukan oleh juri yang ditugaskan Super Admin. Juri: <?= $judges ? e(implode(', ', array_column($judges, 'full_name'))) : 'belum ditugaskan' ?>. Kriteria: <?= $criteria ? e(implode(', ', array_column($criteria, 'name'))) : 'belum diatur' ?>.</p>
</div>

<?php if ($registration && !$can_edit): ?>
	<div class="card">
		<h2>Pendaftaran sudah dikirim</h2>
		<p class="sub">Desa <?= e($village['name']) ?> sudah terdaftar. Data tidak dapat diubah setelah pendaftaran ditutup atau entry dinilai.</p>
		<div class="formGrid mt">
			<div><b>Tema bazaar</b><p><?= e($registration['theme']) ?></p></div>
			<div><b>Nomor stand</b><p><?= (int) $registration['booth_number'] ?></p></div>
			<div><b>Nomor telepon</b><p><?= e($registration['phone']) ?></p></div>
		</div>
	</div>
<?php elseif (!$registration && !$registration_open): ?>
	<div class="card"><h2>Pendaftaran Bazaar Desa belum dibuka</h2><p class="sub">Silakan kembali saat Super Admin sudah membuka pendaftaran.</p></div>
<?php else: ?>
	<form method="post" action="<?= e(site_url('desa/bazaar')) ?>" class="card fields">
		<?= csrf_field() ?>
		<div class="alert info"><?= $registration ? 'Perbarui pendaftaran Bazaar Desa milik ' : 'Pendaftaran ini menggunakan akun Admin Desa ' ?><b><?= e($village['name']) ?></b>. Setiap desa dapat mendaftar satu kali.</div>
		<div class="field"><label for="bazaar-theme">Tema bazaar *</label><input id="bazaar-theme" name="theme" type="text" maxlength="150" required value="<?= e($registration['theme'] ?? '') ?>"></div>
		<div class="field"><label for="bazaar-phone">Nomor HP penanggung jawab *</label><input id="bazaar-phone" name="phone" type="tel" maxlength="30" required value="<?= e($registration['phone'] ?? '') ?>"></div>
		<div class="field"><label for="bazaar-booth">Nomor stand *</label>
			<select id="bazaar-booth" name="booth_number" required>
				<option value="">Pilih nomor stand…</option>
				<?php for ($booth = 1; $booth <= 9; $booth++): ?>
					<?php $taken = in_array($booth, $selected_booths, TRUE) && (int) ($registration['booth_number'] ?? 0) !== $booth; ?>
					<option value="<?= $booth ?>"<?= (int) ($registration['booth_number'] ?? 0) === $booth ? ' selected' : '' ?><?= $taken ? ' disabled' : '' ?>><?= $booth ?><?= $taken ? ' — sudah dipilih' : '' ?></option>
				<?php endfor; ?>
			</select>
		</div>
		<div class="row"><button class="btn primary" type="submit" data-pending-text="Menyimpan…"><?= $registration ? 'Simpan Perubahan' : 'Kirim Pendaftaran' ?></button></div>
	</form>
<?php endif; ?>
