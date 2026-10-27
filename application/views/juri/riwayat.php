<?php /** @var list $rows */ ?>
<?= page_head('Penilaian Tersimpan', 'Riwayat nilai yang sudah disubmit ke hasil lomba. Draft yang belum disubmit dapat diperiksa dari daftar peserta.') ?>

<div class="tableWrap">
	<table>
		<thead><tr><th>No</th><th>Lomba</th><th>Peserta</th><th>Detail</th><th class="num">Total</th><th>Status</th></tr></thead>
		<tbody>
		<?php foreach ($rows as $i => $s): ?>
			<tr>
				<td><?= $i + 1 ?></td>
				<td><?= e($s['competition_name']) ?></td>
				<td><b><?= e($s['entry_label']) ?></b><br><small class="muted"><?= e($s['village_name']) ?></small></td>
				<td class="wrap"><?php foreach ($s['details'] as $k => $d): ?><?= $k ? ' • ' : '' ?><?= e($d['name']) ?>: <b><?= e(fmt_score($d['score'])) ?></b><?php endforeach; ?></td>
				<td class="num"><b><?= e(fmt_score($s['total'])) ?></b></td>
				<td><?= tag('Tersimpan 🔒') ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$rows): ?><tr><td colspan="6" class="empty">Belum ada penilaian tersimpan.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>
