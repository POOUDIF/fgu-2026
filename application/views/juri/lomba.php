<?php /** @var list $competitions; @var array $progress; @var array<int,int> $draftCounts */ ?>
<?= page_head('Lomba Saya', 'Hanya lomba yang mencantumkan akun juri ini.') ?>

<div class="cards">
	<?php foreach ($competitions as $c):
		$p = $progress[$c['id']] ?? array('entries' => 0, 'actual' => 0);
		$ccState = $cc_states[$c['id']] ?? NULL;
		$draftCount = $draftCounts[$c['id']] ?? 0;
		$done = min((int) $p['entries'], (int) $p['actual'] + $draftCount);
		$percent = progress_pct($done, (int) $p['entries']);
	?>
		<div class="card competition">
			<div class="top"><?= comp_icon($c, 'compImg') ?><?= tag($c['submission_mode'] === 'online' ? 'Online' : 'Offline', $c['submission_mode'] === 'online' ? 'blue' : '') ?></div>
			<h3><?= e($c['name']) ?></h3>
			<div class="meta"><?= e($c['age_label']) ?> • <?= e($c['type_label']) ?><br>Peserta masuk: <?= (int) $p['entries'] ?><br>Kriteria: <?= e(implode(', ', array_column($c['criteria'], 'name'))) ?></div>
			<?php if ($c['results_published']): ?><div><?= tag('Hasil dipublikasikan · penilaian ditutup', 'yellow') ?></div><?php endif; ?>
			<div class="judgeCardProgress">
				<?php if ($ccState): ?>
					<?php $roundPercent = (int) round((int) $ccState['current_round'] / 4 * 100); ?>
					<div class="between"><span class="muted"><?= $ccState['is_finished'] ? 'Status' : 'Babak aktif' ?></span><b><?= $ccState['is_finished'] ? 'Selesai' : (int) $ccState['current_round'].'/4' ?></b></div><div class="progressTrack"><span style="width: <?= $ccState['is_finished'] ? 100 : $roundPercent ?>%"></span></div><small class="muted">Papan nilai bersama</small>
				<?php else: ?>
					<div class="between"><span class="muted">Dinilai</span><b><?= $done ?>/<?= (int) $p['entries'] ?></b></div><div class="progressTrack"><span style="width: <?= $percent ?>%"></span></div><small class="muted"><?= (int) $p['actual'] ?> disubmit · <?= $draftCount ?> draft</small>
				<?php endif; ?>
			</div>
			<div class="bottom"><span></span><a class="btn primary" href="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>">Lihat peserta</a></div>
		</div>
	<?php endforeach; ?>
	<?php if (!$competitions): ?><div class="card"><b>Belum ada penugasan lomba.</b></div><?php endif; ?>
</div>
