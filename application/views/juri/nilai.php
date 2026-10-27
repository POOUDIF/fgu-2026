<?php /** @var array $c; @var list $entries; @var array $given [entry_id] => submission */ ?>
<?php if ($c['results_published']): ?><div class="alert info">Hasil sudah dipublikasikan. Penilaian baru ditutup untuk lomba ini.</div><?php endif; ?>

<?php foreach ($entries as $e):
	$id = (int) $e['id'];
	$s = $given[$id] ?? null;
	$values = $s ? array_column($s['details'], 'score', 'criterion_id') : array();
?>
	<div class="judgeCompetitionHeader">
		<h2><?= e($c['name']) ?></h2>
		<a class="btn ghost sm" href="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>">← Daftar peserta</a>
	</div>
	<div class="card judgeEntryBanner" id="entry-<?= $id ?>">
		<div class="judgeEntryInfo">
			<div class="judgeEntryIdentity">
				<?= tag($e['village_name'], 'blue') ?>
				<div><h3><?= e($e['label']) ?></h3>
					<?php if ($e['entry_type'] === 'team' || $c['is_team']): ?><small class="muted"><?= $e['entry_type'] === 'team' ? 'Tim' : 'Regu '.count($e['members']).' orang' ?></small><?php endif; ?>
				</div>
			</div>
			<?php if (!empty($e['members'])): ?><div class="judgeEntryPhotos">
				<?php foreach ($e['members'] as $member): ?>
					<div class="judgeEntryPhoto">
						<?php if (!empty($member['photo_path'])): ?><img src="<?= e(site_url('juri/nilai/'.$c['slug'].'/'.$id.'/foto/'.(int) $member['id'])) ?>" alt="Foto <?= e($member['name']) ?>" loading="lazy"><?php else: ?><div class="photoPlaceholder">Foto belum tersedia</div><?php endif; ?>
						<small><?= e($member['name']) ?></small>
					</div>
				<?php endforeach; ?>
			</div><?php endif; ?>
		</div>
		<?php if ($e['parent_name'] || $e['ig_username'] || $e['members_note']): ?>
			<div class="judgeEntryDetails">
				<?php if ($e['parent_name']): ?><span>Orang tua: <?= e($e['parent_name']) ?></span><?php endif; ?>
				<?php if ($e['ig_username']): ?><span>Instagram: @<?= e($e['ig_username']) ?></span><?php endif; ?>
				<?php if ($e['members_note']): ?><span>Anggota tim: <?= e($e['members_note']) ?></span><?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ($c['submission_mode'] === 'online' && $e['submission_url']): ?>
			<a class="tag blue" target="_blank" rel="noopener noreferrer" href="<?= e($e['submission_url']) ?>">📤 Buka Karya</a>
		<?php endif; ?>
	</div>
	<div class="card judgeScoreCard">
		<?php if (!empty($isCerdasCermat)):
			$roundSpecs = array(
				'babak_1_2' => array('label' => 'Babak 1–2: sistem poin/lempar', 'add' => 100, 'subtract' => NULL),
				'babak_3' => array('label' => 'Babak 3: benar +50, salah −20', 'add' => 50, 'subtract' => 20),
				'final' => array('label' => 'Final: benar +100, salah −50', 'add' => 100, 'subtract' => 50),
			);
			$criteriaByRound = array_values($c['criteria']);
			$disciplinaryTotal = (float) ($otherPenaltyTotal ?? 0);
			$answerTotal = array_sum(array_map('floatval', array_values($values)));
			$previewTotal = $answerTotal - $disciplinaryTotal;
		?>
			<p class="muted">Poin jawaban tersimpan langsung setiap kali tombol ditekan. Pengurangan indisipliner langsung mengurangi pratinjau dan hasil akhir.</p>
			<div class="judgeScoreTableWrap">
				<table class="judgeScoreTable cerdasScoreTable">
					<thead><tr><th>Babak</th><th>Poin saat ini</th><th>Aksi jawaban</th><th>Pengurangan indisipliner</th></tr></thead>
					<tbody>
					<?php foreach ($roundSpecs as $roundKey => $roundSpec):
						$criterion = $criteriaByRound[array_search($roundKey, array_keys($roundSpecs), TRUE)] ?? NULL;
						$criterionId = $criterion ? (int) $criterion['id'] : 0;
						$roundValue = $values[$criterionId] ?? 0;
						$roundPenaltiesForEntry = $roundPenalties[$roundKey] ?? array();
						$roundPenaltyTotal = array_sum(array_map(static fn ($penalty) => (float) $penalty['points'], $roundPenaltiesForEntry));
					?>
						<tr>
							<td><b><?= e($roundSpec['label']) ?></b></td>
							<td><b><?= e(fmt_score($roundValue)) ?></b><small class="muted block">Potongan: <?= e(fmt_score($roundPenaltyTotal)) ?></small></td>
							<td>
								<?php if (!$c['results_published'] && (!$s || !empty($s['is_draft']))): ?>
									<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/'.$id.'/poin')) ?>" class="cerdasPointActions">
										<?= csrf_field() ?>
										<input type="hidden" name="round" value="<?= e($roundKey) ?>">
										<button type="submit" name="change" value="add" class="btn primary sm" data-pending-text="Menyimpan…">+ <?= e(fmt_score($roundSpec['add'])) ?></button>
										<?php if ($roundSpec['subtract'] !== NULL): ?><button type="submit" name="change" value="subtract" class="btn danger sm" data-pending-text="Menyimpan…">− <?= e(fmt_score($roundSpec['subtract'])) ?></button><?php endif; ?>
									</form>
								<?php else: ?><span class="muted">Terkunci</span><?php endif; ?>
							</td>
							<td>
								<?php if (!$c['results_published']): ?>
									<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/'.$id.'/penalti')) ?>" class="cerdasPenaltyForm">
										<?= csrf_field() ?>
										<input type="hidden" name="round" value="<?= e($roundKey) ?>">
										<input type="number" name="points" min="0.01" step="0.01" required aria-label="Poin pengurangan indisipliner <?= e($roundSpec['label']) ?>" placeholder="Poin">
										<input type="text" name="reason" maxlength="500" required aria-label="Alasan pengurangan <?= e($roundSpec['label']) ?>" placeholder="Alasan pengurangan">
										<button type="submit" class="btn danger sm" data-pending-text="Mengurangi…">Kurangkan</button>
									</form>
								<?php endif; ?>
								<?php if ($roundPenaltiesForEntry): ?>
									<ul class="cerdasPenaltyHistory">
										<?php foreach ($roundPenaltiesForEntry as $penalty): ?><li>−<?= e(fmt_score($penalty['points'])) ?> · <?= e($penalty['reason']) ?></li><?php endforeach; ?>
									</ul>
								<?php elseif ($c['results_published'] || ($s && empty($s['is_draft']))): ?><span class="muted">Tidak ada pengurangan</span><?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="judgeScoreActions">
				<div>Pratinjau total setelah pengurangan: <b><?= e(fmt_score($previewTotal)) ?></b>
					<small class="muted block">(Poin jawaban <?= e(fmt_score($answerTotal)) ?> − indisipliner <?= e(fmt_score($disciplinaryTotal)) ?>)</small>
				</div>
				<?php if ($s && empty($s['is_draft'])): ?><span class="locked">Nilai sudah disubmit dan terkunci.</span><?php endif; ?>
			</div>
		<?php else: ?>
		<?php if ($s && empty($s['is_draft'])): ?>
			<div class="locked">🔒 Sudah disubmit — total <b><?= e(fmt_score($s['total'])) ?></b>. Nilai terkunci.
				<br><?php foreach ($s['details'] as $k => $d): ?><?= $k ? ' · ' : '' ?><?= e($d['name']) ?>: <b><?= e(fmt_score($d['score'])) ?></b><?php endforeach; ?>
			</div>
		<?php elseif ($c['results_published']): ?>
			<div class="locked">
				<?= $s ? 'Draft tersimpan — total <b>'.e(fmt_score($s['total'])).'</b>. ' : '' ?>Penilaian tidak dapat diubah atau disubmit setelah hasil dipublikasikan.
				<?php if ($s): ?><br><?php foreach ($s['details'] as $k => $d): ?><?= $k ? ' · ' : '' ?><?= e($d['name']) ?>: <b><?= e(fmt_score($d['score'])) ?></b><?php endforeach; ?><?php endif; ?>
			</div>
		<?php else: ?>
			<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/'.$id)) ?>" data-score-form data-score-method="<?= e($c['scoring_method']) ?>" data-confirm="Simpan nilai <?= e($e['label']) ?> sebagai draft? Draft bisa diperiksa dan diubah sebelum disubmit.">
				<?= csrf_field() ?>
				<?php if ($s): ?><div class="alert info judgeDraftNotice">Draft tersimpan untuk diperiksa. Perubahan belum masuk ke hasil lomba.</div><?php endif; ?>
				<div class="judgeScoreTableWrap">
					<table class="judgeScoreTable">
						<thead><tr><th>Kriteria</th><th>Bobot</th><th>Nilai (50–maks.)</th></tr></thead>
						<tbody>
						<?php foreach ($c['criteria'] as $cr): ?>
							<tr>
								<td><b><?= e($cr['name']) ?></b></td>
								<td><?= $c['scoring_method'] === 'weighted' ? e(fmt_score($cr['weight'])).'%' : '–' ?></td>
								<td><input type="text" inputmode="decimal" name="c[<?= (int) $cr['id'] ?>]" value="<?= isset($values[(int) $cr['id']]) ? e(fmt_score($values[(int) $cr['id']])) : '' ?>" required autocomplete="off" pattern="\d+([.,]\d{1,2})?" placeholder="50–<?= e(fmt_score($cr['max_score'])) ?>" data-score-input data-min="50" data-max="<?= e((float) $cr['max_score']) ?>" data-weight="<?= e((float) $cr['weight']) ?>"></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="judgeScoreActions">
					<div>Pratinjau total: <b data-score-total>–</b></div>
					<button type="submit" class="btn primary" data-pending-text="Menyimpan…">SAVE DRAFT</button>
				</div>
			</form>
		<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
<?php if (!$entries): ?><div class="alert info">Belum ada peserta yang terdaftar pada lomba ini.</div><?php endif; ?>
