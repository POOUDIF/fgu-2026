<?php
/** @var array $village, $summary; @var list $competitions; @var int $participant_count, $registration_count, $pending, $quota_filled, $score_complete; @var string $registration_status */
use App\Domain\RegistrationWindow;
$statusLabels = array(
	RegistrationWindow::OPEN => array('Dibuka', 'ok'),
	RegistrationWindow::NOT_OPEN => array('Belum dibuka', 'info'),
	RegistrationWindow::CLOSED => array('Ditutup', 'err'),
);
[$registrationLabel, $registrationStyle] = $statusLabels[$registration_status] ?? array('Tidak diketahui', 'info');
?>
<?= page_head('Ringkasan Desa', 'Status kontingen '.$village['name'].' dan pendaftaran seluruh lomba.') ?>

<div class="alert <?= e($registrationStyle) ?>">Pendaftaran: <b><?= e($registrationLabel) ?></b><?php if ($registration_status === RegistrationWindow::OPEN): ?> · <?= e($village['name']) ?> dapat mengelola pendaftaran.<?php else: ?> · Data pendaftaran yang sudah tersimpan tetap dapat dilihat.<?php endif; ?></div>

<div class="adminStats villageStats">
	<div class="stat"><b><?= (int) $participant_count ?></b><span>Peserta terdata</span></div>
	<div class="stat"><b><?= (int) $registration_count ?></b><span>Pendaftaran lomba</span></div>
	<div class="stat"><b><?= (int) $quota_filled ?>/<?= count(array_filter($competitions, static fn ($c) => $c['quota'] !== NULL)) ?></b><span>Lomba berkuota terisi</span></div>
	<div class="stat"><b><?= (int) $score_complete ?></b><span>Lomba dengan penilaian lengkap</span></div>
</div>

<div class="card">
	<div class="pageHead compact"><div><h2>Status per lomba</h2><p>Pendaftaran hanya mencakup data kontingen desa Anda.</p></div><a class="btn primary" href="<?= e(site_url('desa/daftar')) ?>">Kelola pendaftaran</a></div>
	<div class="tableWrap">
		<table class="villageStatusTable">
			<thead><tr><th>Lomba</th><th>Kategori</th><th>Terdaftar</th><th>Status</th><th></th></tr></thead>
			<tbody>
			<?php foreach ($competitions as $c):
				$s = $summary[$c['id']] ?? array('entries' => 0, 'participants' => 0);
				$target = $c['quota'];
				$isFull = $target !== NULL && $s['participants'] >= (int) $target;
			?>
				<tr>
					<td><div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><b><?= e($c['name']) ?></b></div></td>
					<td class="muted"><?= e($c['age_label']) ?></td>
					<td><?= (int) $s['participants'] ?><?= $target !== NULL ? ' / '.(int) $target : '' ?></td>
					<td><?= $s['entries'] === 0 ? tag('Belum daftar', 'yellow') : ($isFull ? tag('Kuota terisi') : tag('Sebagian')) ?></td>
					<td><a class="btn soft sm" href="<?= e(site_url('desa/daftar')) ?>#reg-<?= (int) $c['id'] ?>"><?= $s['entries'] ? 'Lihat / Kelola' : 'Daftar' ?></a></td>
				</tr>
			<?php endforeach; ?>
			<?php if (!$competitions): ?><tr><td colspan="5" class="empty">Belum ada lomba aktif.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="adminSettingsLinks">
	<a class="btn soft" href="<?= e(site_url('desa/kontrol-lomba')) ?>">Kontrol lomba</a>
	<a class="btn soft" href="<?= e(site_url('desa/kontrol-nilai')) ?>">Pantau penilaian</a>
	<a class="btn soft" href="<?= e(site_url('desa/peserta')) ?>">Data &amp; foto peserta</a>
	<a class="btn soft" href="<?= e(site_url('desa/karya')) ?>">Pengumpulan karya online</a>
	<a class="btn soft" href="<?= e(site_url('desa/bazaar')) ?>">Daftar Bazaar Desa</a>
</div>
