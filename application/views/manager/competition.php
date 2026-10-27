<?php /** @var array $competition; @var list $entries; @var array $score_map */ ?>
<?= page_head('Review Penilaian — '.$competition['name'], 'Periksa kelengkapan setiap juri. Anda dapat melengkapi nilai yang terlewat dan mengoreksi nilai dengan alasan.') ?>

<?php if (!empty($is_cerdas_cermat)): ?><div class="sectionHead"><a class="btn primary" href="<?= e(site_url('siaran/cerdas-cermat/'.$competition['slug'])) ?>" target="_blank" rel="noopener noreferrer">Tampilkan Papan Penonton ↗</a></div><?php endif; ?>
<?php if ($competition['results_published']): ?>
	<div class="alert info">Hasil lomba telah dipublikasikan. Penilaian tidak dapat diubah.</div>
<?php endif; ?>
<div class="alert info">Nilai tiap kriteria minimal 50 dan tidak boleh melebihi nilai maksimum kriteria. Input atau perubahan nilai oleh Admin Lomba dicatat di audit.</div>

<?php foreach ($entries as $entry): ?>
	<section class="card entryCard">
		<div class="between">
			<div><h2><?= e($entry['label']) ?></h2><p class="muted"><?= e($entry['village_name']) ?></p></div>
			<?= tag(count(array_filter($score_map[(int) $entry['id']] ?? array())).'/'.count($competition['judges']).' juri menilai', count($competition['judges']) === count($score_map[(int) $entry['id']] ?? array()) ? '' : 'yellow') ?>
		</div>
		<?php foreach ($competition['judges'] as $judge):
			$submission = $score_map[(int) $entry['id']][(int) $judge['id']] ?? NULL;
			$scoreByCriterion = array();
			foreach ($submission['details'] ?? array() as $detail)
			{
				$scoreByCriterion[(int) $detail['criterion_id']] = $detail['score'];
			}
		?>
			<div class="card flat">
				<div class="between"><h3><?= e($judge['full_name']) ?></h3><?= $submission ? tag('Nilai masuk') : tag('Belum menilai', 'yellow') ?></div>
				<?php if ($competition['results_published']): ?>
					<?php if ($submission): ?><p class="sub"><?php foreach ($submission['details'] as $i => $detail): ?><?= $i ? ' · ' : '' ?><?= e($detail['name']) ?>: <b><?= e(fmt_score($detail['score'])) ?></b><?php endforeach; ?></p><?php endif; ?>
				<?php else: ?>
					<form method="post" action="<?= e(site_url('admin-lomba/nilai/'.$competition['slug'].'/'.(int) $entry['id'].'/'.(int) $judge['id'])) ?>" class="fields" data-score-form data-score-method="<?= e($competition['scoring_method']) ?>">
						<?= csrf_field() ?>
						<div class="scoreGrid">
							<?php foreach ($competition['criteria'] as $criterion): ?>
								<div class="field">
									<label><?= e($criterion['name']) ?> (50–<?= e(fmt_score($criterion['max_score'])) ?>)</label>
									<input type="text" inputmode="decimal" name="score[<?= (int) $criterion['id'] ?>]" required pattern="\d+([.,]\d{1,2})?" value="<?= isset($scoreByCriterion[(int) $criterion['id']]) ? e(fmt_score($scoreByCriterion[(int) $criterion['id']])) : '' ?>" placeholder="Nilai minimal 50" data-score-input data-min="50" data-max="<?= e((float) $criterion['max_score']) ?>" data-weight="<?= e((float) $criterion['weight']) ?>">
								</div>
							<?php endforeach; ?>
						</div>
						<div>Pratinjau total: <b data-score-total>–</b></div>
						<div class="field"><label>Alasan input / perubahan *</label><input type="text" name="reason" maxlength="500" required placeholder="<?= $submission ? 'Jelaskan alasan koreksi nilai' : 'Contoh: nilai juri terlewat diinput' ?>"></div>
						<button type="submit" class="btn primary" data-pending-text="Menyimpan…"><?= $submission ? 'Simpan Koreksi' : 'Input Nilai Juri' ?></button>
					</form>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php if (!$competition['judges']): ?><p class="muted">Belum ada juri yang ditugaskan pada lomba ini.</p><?php endif; ?>
	</section>
<?php endforeach; ?>
<?php if (!$entries): ?><div class="alert info">Belum ada peserta yang ditandai hadir untuk lomba ini.</div><?php endif; ?>
