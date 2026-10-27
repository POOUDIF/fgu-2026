<?php /** @var list $competitions; @var array $progress; @var int $total_scores */
$complete = count(array_filter($progress, static fn ($p) => $p['complete']));
$active = array_filter($competitions, static fn ($c) => isset($progress[$c['id']]) && $progress[$c['id']]['expected'] > 0);
?>
<?= page_head('Kontrol Penilaian', 'Pantau kelengkapan penilaian seluruh lomba berdasarkan peserta dan seluruh juri yang ditugaskan.') ?>

<div class="kpi">
	<div class="card"><span>Lomba lengkap dinilai</span><b><?= (int) $complete ?></b></div>
	<div class="card"><span>Belum lengkap</span><b><?= count($competitions) - $complete ?></b></div>
	<div class="card"><span>Total penilaian tersimpan</span><b><?= (int) $total_scores ?></b></div>
	<div class="card"><span>Semua lomba lengkap</span><b><?= $complete === count($competitions) && $complete > 0 ? 'Ya' : 'Belum' ?></b></div>
</div>

<div class="controlGrid">
	<?php foreach ($competitions as $c):
		$p = $progress[$c['id']] ?? array('entries' => 0, 'judges' => 0, 'expected' => 0, 'actual' => 0, 'complete' => false);
	?>
		<div class="controlCard">
			<div class="between">
				<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><p><?= (int) $p['entries'] ?> peserta • <?= (int) $p['judges'] ?> juri</p></div></div>
				<?php if ($p['complete']): ?><?= pill('Lengkap', 'ok') ?>
				<?php elseif ($p['expected'] > 0): ?><?= pill('Belum lengkap', 'warn') ?>
				<?php elseif ($p['judges'] === 0): ?><?= pill('Belum ada juri', 'info') ?>
				<?php else: ?><?= pill('Belum ada peserta', 'info') ?><?php endif; ?>
			</div>
			<div class="progressMini"><span style="width: <?= progress_pct($p['actual'], $p['expected']) ?>%"></span></div>
			<p class="mt"><b><?= (int) $p['actual'] ?></b> / <?= (int) $p['expected'] ?> penilaian tersimpan (<?= progress_pct($p['actual'], $p['expected']) ?>%).</p>
		</div>
	<?php endforeach; ?>
</div>
