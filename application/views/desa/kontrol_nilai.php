<?php /** @var array $village; @var list $competitions; @var array $summary, $progress */
$joined = array_values(array_filter($competitions, static fn ($c) => isset($summary[$c['id']])));
$complete = count(array_filter($joined, static fn ($c) => !empty($progress[$c['id']]['complete'])));
?>
<?= page_head('Kontrol Penilaian Desa', 'Status penilaian hanya untuk lomba yang diikuti '.$village['name'].'.') ?>

<div class="kpi">
	<div class="card"><span>Sudah dinilai lengkap</span><b><?= (int) $complete ?></b></div>
	<div class="card"><span>Belum lengkap</span><b><?= count($joined) - $complete ?></b></div>
	<div class="card"><span>Belum ikut lomba</span><b><?= count($competitions) - count($joined) ?></b></div>
	<div class="card"><span>Semua yang diikuti lengkap</span><b><?= $joined && $complete === count($joined) ? 'Ya' : 'Belum' ?></b></div>
</div>

<div class="controlGrid">
	<?php foreach ($joined as $c):
		$p = $progress[$c['id']] ?? array('entries' => 0, 'judges' => 0, 'expected' => 0, 'actual' => 0, 'complete' => false);
	?>
		<div class="controlCard">
			<div class="between">
				<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><p><?= (int) $summary[$c['id']]['participants'] ?> peserta • <?= (int) $p['judges'] ?> juri</p></div></div>
				<?= $p['complete'] ? pill('Lengkap', 'ok') : ($p['judges'] === 0 ? pill('Belum ada juri', 'info') : pill('Belum lengkap', 'warn')) ?>
			</div>
			<div class="progressMini"><span style="width: <?= progress_pct($p['actual'], $p['expected']) ?>%"></span></div>
			<p class="mt"><b><?= (int) $p['actual'] ?></b> / <?= (int) $p['expected'] ?> penilaian tersimpan (<?= progress_pct($p['actual'], $p['expected']) ?>%).</p>
		</div>
	<?php endforeach; ?>
	<?php if (!$joined): ?>
		<div class="controlCard"><b>Belum ada lomba yang diikuti.</b><p class="mt">Daftarkan peserta pada menu <a href="<?= e(site_url('desa/daftar')) ?>">Daftar Peserta Lomba</a>.</p></div>
	<?php endif; ?>
</div>
