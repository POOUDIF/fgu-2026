<?php /** @var list<array<string,mixed>> $competitions; @var list<int> $cerdas_cermat_ids; @var array $progress */ ?>
<?= page_head('Ringkasan Admin Lomba', 'Pantau kelengkapan nilai juri pada lomba yang menjadi tanggung jawab Anda.') ?>

<div class="cards">
	<?php foreach ($competitions as $competition):
		$stats = $progress[$competition['id']] ?? array('entries' => 0, 'judges' => count($competition['judges']), 'expected' => 0, 'actual' => 0);
		$pending = max(0, (int) $stats['expected'] - (int) $stats['actual']);
	?>
		<div class="card judgeTaskCard">
			<div class="withIcon"><?= comp_icon($competition, 'compImg sm') ?><div><h3><?= e($competition['name']) ?></h3><small class="muted"><?= e($competition['age_label']) ?></small></div></div>
			<p><?= (int) $stats['entries'] ?> peserta · <?= (int) $stats['judges'] ?> juri · <?= count($competition['criteria']) ?> kriteria</p>
			<div class="judgeCardProgress"><div class="between"><span><?= $pending ? 'Nilai belum lengkap' : 'Semua nilai sudah masuk' ?></span><b><?= (int) $stats['actual'] ?>/<?= (int) $stats['expected'] ?></b></div><div class="progressTrack"><span style="width: <?= progress_pct((int) $stats['actual'], (int) $stats['expected']) ?>%"></span></div></div>
			<a class="btn primary" href="<?= e(site_url('admin-lomba/nilai/'.$competition['slug'])) ?>">Review / Input Nilai</a>
			<?php if (in_array((int) $competition['id'], $cerdas_cermat_ids, TRUE)): ?><a class="btn ghost" href="<?= e(site_url('siaran/cerdas-cermat/'.$competition['slug'])) ?>" target="_blank" rel="noopener noreferrer">Tampilkan Papan Penonton ↗</a><?php endif; ?>
		</div>
	<?php endforeach; ?>
	<?php if (!$competitions): ?><div class="card"><b>Belum ada penugasan lomba.</b><p class="sub">Hubungi Admin Daerah untuk mengatur lomba yang menjadi tanggung jawab Anda.</p></div><?php endif; ?>
</div>
