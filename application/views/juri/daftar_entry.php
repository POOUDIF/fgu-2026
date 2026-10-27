<?php
/** @var array $c; @var list $entries; @var array<int,true> $scored; @var array<int,true> $drafted */
$done = count(array_filter($entries, static fn ($entry) => isset($scored[(int) $entry['id']]) || isset($drafted[(int) $entry['id']])));
$finalCount = count(array_filter($entries, static fn ($entry) => isset($scored[(int) $entry['id']])));
$readyToSubmit = count($entries) > 0 && $done === count($entries) && $finalCount < count($entries) && !$c['results_published'];
?>
<nav class="crumbs"><a href="<?= e(site_url('juri')) ?>">Ringkasan</a> / <span><?= e($c['name']) ?></span></nav>
<?= page_head($c['name'], $c['age_label'].' · Tersimpan '.$done.'/'.count($entries), '<a class="btn ghost sm" href="'.e(site_url('juri')).'">Kembali</a>') ?>

<?php if ($c['results_published']): ?><div class="alert info">Hasil lomba ini sudah dipublikasikan; penilaian baru ditutup.</div><?php endif; ?>

<div class="card judgeSubmitSummary">
	<div>
		<b><?= $finalCount ?> sudah disubmit · <?= max(0, $done - $finalCount) ?> draft tersimpan · <?= max(0, count($entries) - $done) ?> belum dinilai</b>
		<div class="muted small">Draft bisa diperiksa dan diubah sebelum disubmit ke hasil lomba.</div>
	</div>
	<?php if ($readyToSubmit): ?>
		<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/submit')) ?>" data-confirm="Submit seluruh nilai lomba ini? Nilai akan masuk ke database hasil dan terkunci.">
			<?= csrf_field() ?>
			<button type="submit" class="btn primary" data-pending-text="Mengirim…">SUBMIT SEMUA NILAI</button>
		</form>
	<?php elseif ($finalCount === count($entries) && count($entries) > 0): ?>
		<?= tag('Semua nilai sudah disubmit', 'green') ?>
	<?php else: ?>
		<button type="button" class="btn ghost" disabled>SUBMIT setelah semua peserta dinilai</button>
	<?php endif; ?>
</div>

<?php if (!$entries): ?>
	<div class="card"><p class="muted">Belum ada peserta terdaftar pada lomba ini.</p></div>
<?php else: ?>
	<div class="card judgeEntryPicker">
		<div>
			<h3>Pilih peserta yang akan dinilai</h3>
			<p class="muted small">Pilih nama peserta untuk membuka form penilaiannya. Status draft dan submit ditampilkan di daftar.</p>
		</div>
		<form method="get" action="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>" class="judgeEntryPickerForm">
			<label for="judge-entry-select">Peserta (<?= count($entries) ?>)</label>
			<div class="judgeEntryPickerControls">
				<select id="judge-entry-select" name="entry_id" required>
					<option value="" selected disabled>Pilih peserta…</option>
					<?php foreach ($entries as $entry):
						$id = (int) $entry['id'];
						$isScored = isset($scored[$id]);
						$isDrafted = !$isScored && isset($drafted[$id]);
						$status = $isScored ? 'Sudah disubmit' : ($isDrafted ? 'Draft tersimpan' : 'Belum dinilai');
					?>
						<option value="<?= $id ?>"><?= e($entry['label'].' · '.$entry['village_name'].' · '.$status) ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="btn primary">BUKA FORM PENILAIAN</button>
			</div>
		</form>
	</div>
<?php endif; ?>
