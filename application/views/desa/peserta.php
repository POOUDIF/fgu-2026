<?php
/** @var list<array<string,mixed>> $entries; @var array $village */
$rows = array();
foreach ($entries as $entry)
{
	if (trim((string) ($entry['team_name'] ?? '')) !== '')
	{
		$rows[] = array(
			'participant_id' => NULL,
			'name' => $entry['team_name'],
			'gender' => $entry['gender'],
			'education_level' => NULL,
			'grade' => NULL,
			'parent_name' => $entry['parent_name'],
			'photo_path' => NULL,
			'entry' => $entry,
		);
		continue;
	}
	foreach ($entry['members'] as $member)
	{
		$rows[] = array(
			'participant_id' => (int) $member['id'],
			'name' => $member['name'],
			'gender' => $member['gender'],
			'education_level' => $member['education_level'],
			'grade' => $member['grade'],
			'parent_name' => $member['parent_name'],
			'photo_path' => $member['photo_path'],
			'entry' => $entry,
		);
	}
}
?>
<?= page_head('Data & Foto Peserta', 'Kelola peserta dari seluruh lomba kontingen '.$village['name'].'.', '<a class="btn primary" href="'.e(site_url('desa/daftar')).'">Kelola pendaftaran lomba</a>') ?>

<div class="alert info">Daftar ini memuat peserta dari semua lomba yang terdaftar atas nama desa Anda, termasuk pendaftaran langsung seperti Lomba Mewarnai. Perubahan hanya dapat dilakukan selama pendaftaran masih dibuka dan sebelum entry dinilai. Foto disimpan privat dan hanya dapat dilihat akun Admin Desa desa ini. Maksimum 2 MB; format JPG, PNG, atau WebP.</div>
<div class="tableWrap">
	<table>
		<thead><tr><th>Foto</th><th>Nama peserta / tim</th><th>L/P</th><th>Kelas</th><th>Lomba</th><th>Status</th><th>Aksi</th></tr></thead>
		<tbody>
		<?php foreach ($rows as $row):
			$entry = $row['entry'];
			$isOpenEntry = $entry['registration_mode'] === 'open';
		?>
			<tr>
				<td>
					<?php if (!empty($row['photo_path']) && $row['participant_id']): ?>
						<img src="<?= e(site_url('desa/peserta/'.$row['participant_id'].'/foto')) ?>" alt="<?= e('Foto '.$row['name']) ?>" width="48" height="48" style="object-fit:cover;border-radius:8px">
					<?php elseif ($row['participant_id']): ?>
						<span class="muted">Belum ada</span>
					<?php else: ?>
						<span class="muted">—</span>
					<?php endif; ?>
				</td>
				<td><b><?= e($row['name']) ?></b><?php if (!empty($entry['members_note'])): ?><br><small class="muted"><?= e($entry['members_note']) ?></small><?php endif; ?></td>
				<td><?= ($row['gender'] ?? NULL) === 'L' ? 'Putra' : (($row['gender'] ?? NULL) === 'P' ? 'Putri' : '—') ?></td>
				<td><?= e($row['education_level'] ?? '–') ?><?= !empty($row['grade']) ? ' '.(int) $row['grade'] : '' ?></td>
				<td><span class="tag blue"><?= e($entry['competition_name']) ?></span></td>
				<td><?= e($entry['status'] === 'registered' ? 'Terdaftar' : ucfirst($entry['status'])) ?></td>
				<td>
					<?php if ($entry['competition_slug'] === 'bazaar-desa'): ?>
						<a class="btn ghost sm" href="<?= e(site_url('desa/bazaar')) ?>">Kelola Bazaar Desa</a>
					<?php elseif ($isOpenEntry): ?>
						<a class="btn ghost sm" href="<?= e(site_url('desa/peserta/'.(int) $entry['id'].'/ubah')) ?>">Ubah data</a>
					<?php else: ?>
						<a class="btn ghost sm" href="<?= e(site_url('desa/daftar')) ?>#reg-<?= (int) $entry['competition_id'] ?>">Ubah data pendaftaran</a>
					<?php endif; ?>
					<?php if ($row['participant_id']): ?>
						<form method="post" enctype="multipart/form-data" action="<?= e(site_url('desa/peserta/'.$row['participant_id'].'/foto')) ?>" class="inlineForm">
							<?= csrf_field() ?>
							<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
							<button class="btn soft sm" type="submit">Simpan foto</button>
						</form>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if ($rows === array()): ?><tr><td colspan="7" class="empty">Belum ada peserta terdaftar di desa ini.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>
