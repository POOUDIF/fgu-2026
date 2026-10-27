<?php
/** @var array $c; @var array $state; @var array $rounds; @var list $results */
$roundNumber = (int) $state['current_round'];
$finished = (bool) $state['is_finished'];
$closed = $finished || !empty($c['results_published']);
$rankedResults = array_values(array_filter($results, static fn ($result) => $result['rank'] !== NULL));
$roundKey = 'babak_'.$roundNumber;
$roundSpec = $rounds[$roundNumber];
$roundLabel = $roundSpec['label'];
?>
<?= page_head('Papan Cerdas Cermat', $c['name'].' · Penilaian bersama per desa, tersimpan langsung setiap tombol ditekan.') ?>

<div class="card ccRoundHeader">
	<div>
		<div class="eyebrow"><?= $finished ? 'SELESAI' : (!empty($c['results_published']) ? 'DITUTUP' : 'BABAK AKTIF') ?></div>
		<h2><?= $finished ? 'Cerdas Cermat selesai' : (!empty($c['results_published']) ? 'Penilaian telah ditutup' : e($roundLabel).' dari 4 babak') ?></h2>
		<p class="muted"><?= $finished ? 'Seluruh nilai akhir dan peringkat sudah tersimpan.' : (!empty($c['results_published']) ? 'Hasil lomba sudah dipublikasikan.' : 'Nilai desa yang tidak mendapat tombol poin pada babak ini otomatis 0 saat lanjut.') ?></p>
	</div>
	<div class="ccRoundProgress" aria-label="Progres babak">
		<?php foreach ($rounds as $number => $spec): ?><span class="<?= $number <= $roundNumber ? 'active' : '' ?><?= $finished ? ' complete' : '' ?>"><?= e((string) $number) ?></span><?php endforeach; ?>
	</div>
	<?php if (!$closed): ?>
		<div class="ccRoundNavigation">
			<?php if ($roundNumber > 1): ?>
				<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/babak/'.$roundNumber.'/kembali')) ?>" data-confirm="Kembali ke Babak <?= $roundNumber - 1 ?>? Nilai yang sudah tersimpan tetap ada.">
					<?= csrf_field() ?><button class="btn ghost" type="submit">← Kembali ke Babak <?= $roundNumber - 1 ?></button>
				</form>
			<?php endif; ?>
			<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/babak/'.$roundNumber.'/lanjut')) ?>" data-confirm="<?= $roundNumber === 4 ? 'Selesaikan Cerdas Cermat? Setelah selesai, nilai tidak dapat diubah.' : 'Simpan Babak '.$roundNumber.' dan lanjut ke Babak '.($roundNumber + 1).'? Desa tanpa aksi poin dihitung 0.' ?>">
				<?= csrf_field() ?><button class="btn primary" type="submit" data-pending-text="Menyimpan…"><?= $roundNumber === 4 ? 'SELESAI' : 'LANJUT KE BABAK '.($roundNumber + 1).' →' ?></button>
			</form>
		</div>
	<?php endif; ?>
</div>

<div class="card sectionCard">
	<div class="sectionHead"><div><h2>Peringkat sementara</h2><p>Total berjalan = jumlah nilai bersih dari seluruh babak. Nilai babak yang belum dimainkan dihitung 0.</p></div></div>
	<?php if (!$rankedResults): ?>
		<p class="sub">Belum ada desa yang ditandai hadir untuk lomba ini.</p>
	<?php else: ?>
		<div class="tableWrap">
			<table class="compact ccScoreBoard">
				<thead><tr><th>#</th><th>Desa / Peserta</th><th><?= e($roundLabel) ?></th><th>Aksi jawaban</th><th>Pengurangan indisipliner</th><th>Total sementara</th></tr></thead>
				<tbody>
				<?php foreach ($rankedResults as $result):
					$id = (int) $result['id'];
					$roundScore = (float) ($result['round_scores'][$roundNumber] ?? 0);
					$roundPenalties = array_values(array_filter($result['penalty_rows'], static fn ($penalty) => ($penalty['round_key'] ?? '') === $roundKey));
				?>
					<tr>
						<td><span class="rank <?= $result['rank'] <= 3 ? 'r'.(int) $result['rank'] : '' ?>"><?= (int) $result['rank'] ?></span></td>
						<td><b><?= e($result['village_name']) ?></b><small class="block"><?= e($result['label']) ?></small></td>
						<td><b><?= e(fmt_score($roundScore)) ?></b><?php if ($roundPenalties): ?><small class="muted block">Potongan: <?= e(fmt_score(array_sum(array_map(static fn ($penalty) => (float) $penalty['points'], $roundPenalties)))) ?></small><?php endif; ?></td>
						<td>
							<?php if (!$closed): ?>
								<div class="ccPointActions">
									<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/babak/'.$roundNumber.'/entry/'.$id.'/poin')) ?>">
										<?= csrf_field() ?><input type="hidden" name="change" value="add"><button class="btn primary sm" type="submit" data-pending-text="…">+ <?= e(fmt_score($roundSpec['add'])) ?></button>
									</form>
									<?php if ($roundSpec['subtract'] !== NULL): ?><form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/babak/'.$roundNumber.'/entry/'.$id.'/poin')) ?>">
										<?= csrf_field() ?><input type="hidden" name="change" value="subtract"><button class="btn danger sm" type="submit" data-pending-text="…">− <?= e(fmt_score($roundSpec['subtract'])) ?></button>
									</form><?php endif; ?>
								</div>
							<?php else: ?><span class="muted">Selesai</span><?php endif; ?>
						</td>
						<td>
							<?php if (!$closed): ?>
								<form method="post" action="<?= e(site_url('juri/nilai/'.$c['slug'].'/babak/'.$roundNumber.'/entry/'.$id.'/penalti')) ?>" class="ccPenaltyForm">
									<?= csrf_field() ?>
									<input type="number" name="points" min="0.01" step="0.01" required aria-label="Poin pengurangan untuk <?= e($result['village_name']) ?>" placeholder="Poin">
									<input type="text" name="reason" maxlength="500" required aria-label="Alasan pengurangan untuk <?= e($result['village_name']) ?>" placeholder="Alasan pengurangan">
									<button type="submit" class="btn danger sm" data-pending-text="…">Kurangkan</button>
								</form>
							<?php endif; ?>
							<?php if ($roundPenalties): ?><ul class="cerdasPenaltyHistory">
								<?php foreach ($roundPenalties as $penalty): ?><li>−<?= e(fmt_score($penalty['points'])) ?> · <?= e($penalty['reason']) ?></li><?php endforeach; ?>
							</ul><?php endif; ?>
						</td>
						<td><b><?= e(fmt_score($result['final_score'])) ?></b></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
