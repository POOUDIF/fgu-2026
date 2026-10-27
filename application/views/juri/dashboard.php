<?php /** @var list $competitions; @var array $progress; @var array<int,int> $draftCounts; @var int $to_score, $scored */ ?>
<p class="judgeWelcome">Pilih lomba yang akan Anda nilai.</p>

<div class="cards g4 judgeCompetitionGrid">
	<?php foreach ($competitions as $c):
		$p = $progress[$c['id']] ?? array('entries' => 0, 'actual' => 0);
		$total = (int) $p['entries'];
		$final = (int) $p['actual'];
		$drafted = $draftCounts[$c['id']] ?? 0;
		$ccState = $cc_states[$c['id']] ?? NULL;
		$done = min($total, $final + $drafted);
		$percent = progress_pct($done, $total);
	?>
		<a class="card competition judgeCompetition" href="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>">
			<div class="judgeCompetitionTop"><?= comp_icon($c, 'compImg') ?><?= tag($c['cluster'] ?: 'Lomba', 'blue') ?></div>
			<h3><?= e($c['name']) ?></h3>
			<p class="judgeCompetitionAge"><?= e($c['age_label']) ?></p>
			<?php if ($c['results_published']): ?><div><?= tag('Hasil dipublikasikan · penilaian ditutup', 'yellow') ?></div><?php endif; ?>
			<div class="judgeCardProgress">
				<?php if ($ccState): ?>
					<?php $roundPercent = (int) round((int) $ccState['current_round'] / 4 * 100); ?>
					<div class="between"><span class="muted"><?= $ccState['is_finished'] ? 'Status' : 'Babak aktif' ?></span><b><?= $ccState['is_finished'] ? 'Selesai' : (int) $ccState['current_round'].'/4' ?></b></div>
					<div class="progressTrack"><span style="width: <?= $ccState['is_finished'] ? 100 : $roundPercent ?>%"></span></div>
					<small class="muted">Papan nilai bersama · <?= (int) $p['entries'] ?> desa</small>
				<?php else: ?>
					<div class="between"><span class="muted">Dinilai</span><b><?= $done ?>/<?= $total ?></b></div>
					<div class="progressTrack"><span style="width: <?= $percent ?>%"></span></div>
					<small class="muted"><?= $final ?> disubmit · <?= $drafted ?> draft</small>
				<?php endif; ?>
			</div>
			<span class="judgeCardHint"><?= $c['results_published'] ? 'Lihat peserta' : 'Buka penilaian' ?> →</span>
		</a>
	<?php endforeach; ?>
	<?php if (!$competitions): ?><div class="card"><h2>Belum ada lomba yang ditugaskan</h2><p class="muted">Hubungi panitia untuk menugaskan Anda ke satu atau beberapa lomba.</p></div><?php endif; ?>
</div>
