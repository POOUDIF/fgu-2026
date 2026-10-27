<?php /** @var list $competitions, $villages; @var array $counts [competition][village] => jumlah entry */
$total = count($competitions) * count($villages);
$done = 0;
foreach ($competitions as $c) { $done += count($counts[$c['id']] ?? array()); }
?>
<?= page_head('Kontrol Lomba', 'Pantau desa mana yang sudah dan belum mendaftarkan peserta pada setiap lomba.') ?>

<div class="kpi">
	<div class="card"><span>Total lomba</span><b><?= count($competitions) ?></b></div>
	<div class="card"><span>Desa</span><b><?= count($villages) ?></b></div>
	<div class="card"><span>Sudah terdaftar</span><b><?= (int) $done ?></b></div>
	<div class="card"><span>Belum terdaftar</span><b><?= max(0, $total - $done) ?></b></div>
</div>

<div class="controlGrid">
	<?php foreach ($competitions as $c):
		$per = $counts[$c['id']] ?? array();
		$pending = array_filter($villages, static fn ($v) => !isset($per[(int) $v['id']]));
	?>
		<div class="controlCard">
			<div class="between">
				<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><p><?= e($c['cluster']) ?> • Kuota <?= e(quota_label($c['quota'])) ?>/desa</p></div></div>
				<?= $pending ? pill(count($per).' desa terdaftar', 'warn') : pill('Semua desa terdaftar', 'ok') ?>
			</div>
			<div class="progressMini"><span style="width: <?= progress_pct(count($per), count($villages)) ?>%"></span></div>
			<div class="villageStatusGrid">
				<?php foreach ($villages as $v): $yes = isset($per[(int) $v['id']]); ?>
					<div class="villageStatus"><?= $yes ? '🟢' : '⚪' ?> <b><?= e($v['name']) ?></b><small><?= $yes ? 'Sudah daftar' : 'Belum daftar' ?></small></div>
				<?php endforeach; ?>
			</div>
			<?php if ($pending): ?>
				<p class="note bad"><b>Belum:</b> <?= e(implode(', ', array_column($pending, 'name'))) ?></p>
			<?php else: ?>
				<p class="note good"><b>Lengkap:</b> seluruh desa sudah memiliki pendaftaran.</p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
