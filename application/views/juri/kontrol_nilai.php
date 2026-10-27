<?php /** @var list $competitions; @var array $progress; @var array<int,int> $draftCounts; @var int $my_total */
$standardCompetitions = array_values(array_filter($competitions, static fn ($c) => !isset($cc_states[(int) $c['id']])));
$complete = count(array_filter($standardCompetitions, static fn ($c) => !empty($progress[$c['id']]['complete'])));
$ready = 0;
$incomplete = 0;
foreach ($standardCompetitions as $competition)
{
	$p = $progress[$competition['id']] ?? array('entries' => 0, 'actual' => 0);
	$draftCount = $draftCounts[$competition['id']] ?? 0;
	if ((int) $p['entries'] > 0 && (int) $p['actual'] < (int) $p['entries'] && (int) $p['actual'] + $draftCount >= (int) $p['entries'])
	{
		$ready++;
	}
	elseif ((int) $p['entries'] > 0 && (int) $p['actual'] < (int) $p['entries'] && (int) $p['actual'] + $draftCount < (int) $p['entries'])
	{
		$incomplete++;
	}
}
?>
<?= page_head('Kontrol Penilaian Juri', 'Status penilaian pribadi untuk lomba yang ditugaskan kepada '.$current_user['full_name'].'.') ?>

<div class="kpi">
	<div class="card"><span>Lomba ditugaskan</span><b><?= count($competitions) ?></b></div>
	<div class="card"><span>Sudah disubmit</span><b><?= (int) $complete ?></b></div>
	<div class="card"><span>Draft siap submit</span><b><?= (int) $ready ?></b></div>
	<div class="card"><span>Perlu dinilai</span><b><?= (int) $incomplete ?></b></div>
	<div class="card"><span>Total nilai saya</span><b><?= (int) $my_total ?></b></div>
</div>

<div class="controlGrid">
	<?php foreach ($competitions as $c):
		$p = $progress[$c['id']] ?? array('entries' => 0, 'expected' => 0, 'actual' => 0, 'complete' => false);
		$ccState = $cc_states[$c['id']] ?? NULL;
		$draftCount = $draftCounts[$c['id']] ?? 0;
		$done = min((int) $p['entries'], (int) $p['actual'] + $draftCount);
		$readyToSubmit = (int) $p['entries'] > 0 && $done >= (int) $p['entries'] && (int) $p['actual'] < (int) $p['entries'];
	?>
		<div class="controlCard">
			<div class="between">
				<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><p><?= (int) $p['entries'] ?> peserta<?= $ccState ? ' • 4 babak' : ' • '.count($c['criteria']).' kriteria' ?></p></div></div>
				<?= $ccState ? pill($ccState['is_finished'] ? 'Selesai' : 'Babak '.(int) $ccState['current_round'].'/4', $ccState['is_finished'] ? 'ok' : 'info') : ($p['complete'] ? pill('Semua sudah disubmit', 'ok') : ($readyToSubmit ? pill('Draft siap submit', 'info') : ($p['entries'] ? pill('Belum lengkap', 'warn') : pill('Belum ada peserta', 'info')))) ?>
			</div>
			<div class="progressMini"><span style="width: <?= $ccState ? ($ccState['is_finished'] ? 100 : (int) round((int) $ccState['current_round'] / 4 * 100)) : progress_pct($done, (int) $p['entries']) ?>%"></span></div>
			<p class="mt"><?php if ($ccState): ?>Papan nilai bersama · <?= $ccState['is_finished'] ? 'Cerdas Cermat selesai.' : 'Babak '.(int) $ccState['current_round'].' sedang berjalan.' ?><?php else: ?><b><?= (int) $p['actual'] ?> disubmit</b> · <?= $draftCount ?> draft tersimpan · <?= max(0, (int) $p['entries'] - $done) ?> belum dinilai.<?php endif; ?></p>
			<a class="btn primary" href="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>">Buka Penilaian</a>
		</div>
	<?php endforeach; ?>
	<?php if (!$competitions): ?><div class="controlCard"><b>Tidak ada lomba yang ditugaskan.</b></div><?php endif; ?>
</div>
