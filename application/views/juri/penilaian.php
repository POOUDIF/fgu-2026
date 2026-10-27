<?php /** @var list $competitions; @var array $progress; @var array<int,int> $draftCounts */ ?>
<?= page_head('Penilaian', 'Pilih lomba yang Anda juri, lalu nilai setiap peserta.') ?>

<div class="cards">
	<?php foreach ($competitions as $c):
		$p = $progress[$c['id']] ?? array('entries' => 0, 'actual' => 0);
		$ccState = $cc_states[$c['id']] ?? NULL;
		$draftCount = $draftCounts[$c['id']] ?? 0;
		$done = min((int) $p['entries'], (int) $p['actual'] + $draftCount);
		$pending = max(0, (int) $p['entries'] - $done);
	?>
		<div class="card judgeTaskCard">
			<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><small class="muted"><?= e($c['age_label']) ?></small></div></div>
			<p><?= (int) $p['entries'] ?> peserta<?= $ccState ? ' • 4 babak' : ' • '.count($c['criteria']).' kriteria' ?></p>
			<div class="judgeCardProgress">
				<?php if ($ccState): ?>
					<?php $roundPercent = (int) round((int) $ccState['current_round'] / 4 * 100); ?>
					<div class="between"><span><?= $ccState['is_finished'] ? 'Selesai' : 'Babak aktif' ?></span><b><?= $ccState['is_finished'] ? '4/4' : (int) $ccState['current_round'].'/4' ?></b></div><div class="progressTrack"><span style="width: <?= $ccState['is_finished'] ? 100 : $roundPercent ?>%"></span></div><small class="muted">Nilai bersama tersimpan langsung</small>
				<?php else: ?>
					<div class="between"><span><?= (int) $p['entries'] === 0 ? 'Belum ada peserta' : ($pending ? 'Belum semua dinilai' : ($draftCount ? 'Siap diperiksa & submit' : 'Semua sudah disubmit')) ?></span><b><?= $done ?>/<?= (int) $p['entries'] ?></b></div><div class="progressTrack"><span style="width: <?= progress_pct($done, (int) $p['entries']) ?>%"></span></div><small class="muted"><?= (int) $p['actual'] ?> disubmit · <?= $draftCount ?> draft</small>
				<?php endif; ?>
			</div>
			<?php if ($c['results_published']): ?><div><?= tag('Hasil dipublikasikan · penilaian ditutup', 'yellow') ?></div><?php endif; ?>
			<a class="btn primary" href="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>"><?= $pending && !$c['results_published'] ? 'Mulai / Lanjutkan' : ($draftCount ? 'Periksa & submit' : 'Lihat peserta') ?></a>
		</div>
	<?php endforeach; ?>
	<?php if (!$competitions): ?><div class="card"><b>Belum ada penugasan lomba.</b></div><?php endif; ?>
</div>
