<?php /** @var array $village; @var list $blocks */ ?>
<?= page_head('Hasil', 'Hasil lomba yang telah dipublikasikan untuk kontingen '.$village['name'].'.') ?>

<?php if (!$blocks): ?>
	<div class="alert info">Belum ada hasil lomba yang dipublikasikan untuk desa Anda.</div>
<?php endif; ?>

<?php foreach ($blocks as $block): ?>
	<?php $isCerdasCermat = strtolower((string) $block['competition']['slug']) === 'cerdas-cermat'; ?>
	<section class="card">
		<h2><?= e($block['competition']['name']) ?></h2>
		<p class="muted"><?= e($block['competition']['age_label']) ?> · Hasil telah dipublikasikan</p>
		<div class="tableWrap">
			<table>
				<thead><tr><th>Peringkat</th><th>Peserta</th><?php if ($isCerdasCermat): ?><th>Babak 1</th><th>Babak 2</th><th>Babak 3</th><th>Babak 4</th><?php endif; ?><th>Nilai akhir</th><th></th></tr></thead>
				<tbody>
				<?php foreach ($block['rows'] as $r): ?>
					<tr>
						<td><?= $r['rank'] !== NULL ? '<span class="rank '.($r['rank'] <= 3 ? 'r'.(int) $r['rank'] : '').'">'.e((string) $r['rank']).'</span>' : '—' ?></td>
						<td><?php if (!empty($r['slot_label'])): ?><?= tag($r['slot_label'], 'blue') ?><?php endif; ?><b><?= e($r['label']) ?></b><?php if ($r['status'] === 'disqualified'): ?><br><?= tag('Didiskualifikasi', 'red') ?><?php endif; ?></td>
						<?php if ($isCerdasCermat): foreach ($r['round_scores'] as $roundScore): ?><td><?= e(fmt_score($roundScore)) ?></td><?php endforeach; endif; ?>
						<td><b><?= $r['final_score'] !== NULL ? e(fmt_score($r['final_score'])) : '—' ?></b></td>
						<td><?= $r['is_winner'] ? tag('Pemenang') : '' ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
<?php endforeach; ?>
