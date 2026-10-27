<?php /** @var array $village; @var list $competitions; @var array $summary */
$done = count(array_filter($competitions, static fn ($c) => isset($summary[$c['id']])));
$pending = count($competitions) - $done;
?>
<?= page_head('Kontrol Lomba Desa', 'Pantau pendaftaran khusus '.$village['name'].'. Desa tidak dapat melihat atau mengubah pendaftaran desa lain.') ?>

<div class="kpi">
	<div class="card"><span>Sudah daftar</span><b><?= (int) $done ?></b></div>
	<div class="card"><span>Belum daftar</span><b><?= (int) $pending ?></b></div>
	<div class="card"><span>Total lomba</span><b><?= count($competitions) ?></b></div>
	<div class="card"><span>Progres</span><b><?= progress_pct($done, count($competitions)) ?>%</b></div>
</div>

<div class="controlGrid">
	<?php foreach ($competitions as $c):
		$s = $summary[$c['id']] ?? null;
	?>
		<div class="controlCard">
			<div class="between">
				<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><p><?= e($c['age_label']) ?> • <?= e($c['type_label']) ?></p></div></div>
				<?= $s ? pill('Sudah daftar', 'ok') : pill('Belum daftar', 'warn') ?>
			</div>
			<p class="mt">Peserta: <b><?= (int) ($s['participants'] ?? 0) ?><?= $c['quota'] !== null ? '/'.(int) $c['quota'] : '' ?></b> orang. <?= $c['submission_mode'] === 'online' ? 'Karya dikumpulkan online.' : 'Pendaftaran melalui Admin Desa.' ?></p>
			<a class="btn <?= $s ? 'soft' : 'primary' ?>" href="<?= e(site_url('desa/daftar')) ?>#reg-<?= (int) $c['id'] ?>"><?= $s ? 'Lihat / Kelola' : 'Daftar Sekarang' ?></a>
		</div>
	<?php endforeach; ?>
</div>
