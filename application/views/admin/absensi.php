<?php /** @var list<array> $entries; @var list<array> $competitions; @var list<array> $villages */ ?>
<?= page_head('Absensi Peserta', 'Verifikasi kehadiran dan kecocokan biodata peserta sebelum penilaian dimulai.') ?>
<div class="card sectionCard">
	<form method="get" action="<?= e(site_url('admin/absensi')) ?>" class="fields">
		<div class="formGrid">
			<div class="field"><label for="att-village">Filter desa</label><select id="att-village" name="desa"><option value="">Semua desa</option><?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>"<?= (string) $f_desa === (string) $v['id'] ? ' selected' : '' ?>><?= e($v['name']) ?></option><?php endforeach; ?></select></div>
			<div class="field"><label for="att-comp">Filter lomba</label><select id="att-comp" name="lomba"><option value="">Semua lomba</option><?php foreach ($competitions as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (string) $f_lomba === (string) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
			<div class="field"><label for="att-status">Status kehadiran</label><select id="att-status" name="status"><option value="">Semua status</option><?php foreach (array('pending' => 'Belum diperiksa', 'present' => 'Hadir', 'absent' => 'Tidak hadir') as $key => $label): ?><option value="<?= e($key) ?>"<?= $f_status === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
			<div class="field"><label for="att-name">Cari nama</label><input id="att-name" name="nama" type="search" value="<?= e($f_nama) ?>" maxlength="100" placeholder="Ketik nama peserta"></div>
		</div>
		<button class="btn primary" type="submit">Terapkan Filter</button>
	</form>
</div>
<div class="card sectionCard">
	<div class="sectionHead"><div><h2>Daftar peserta terdaftar</h2><p>Hanya status Hadir yang sah mengikuti lomba dan ditampilkan ke juri.</p></div></div>
	<?php if ($entries): ?><div class="tableWrap"><table class="compact"><thead><tr><th>Peserta &amp; biodata</th><th>Lomba / Desa</th><th>Status</th><th>Verifikasi</th></tr></thead><tbody>
		<?php foreach ($entries as $entry): ?><tr><td><b><?= e($entry['label']) ?></b><div class="attendanceMembers">
			<?php foreach ($entry['members'] as $member): ?><div class="attendanceMember">
				<?php if (!empty($member['photo_path'])): ?><img src="<?= e(site_url('admin/absensi/foto/'.(int) $member['id'])) ?>" alt="Foto <?= e($member['name']) ?>" loading="lazy"><?php else: ?><div class="photoPlaceholder">Foto belum tersedia</div><?php endif; ?>
				<div><b><?= e($member['name']) ?></b><small><?= e($member['age'] !== NULL ? $member['age'].' tahun' : 'Umur belum diisi') ?> · <?= e($member['gender'] ?: 'Jenis kelamin belum diisi') ?></small><small>Orang tua: <?= e($member['parent_name'] ?: '-') ?></small><small><?= e(trim(($member['education_level'] ?? '').' '.($member['grade'] ?? '')) ?: 'Pendidikan belum diisi') ?></small></div>
			</div><?php endforeach; ?>
		</div></td><td><?= e($entry['competition_name']) ?><br><small><?= e($entry['village_name']) ?></small></td>
		<td><?php $labels = array('pending' => 'Belum diperiksa', 'present' => 'Hadir', 'absent' => 'Tidak hadir'); ?><?= tag($labels[$entry['attendance_status']] ?? 'Belum diperiksa', $entry['attendance_status'] === 'present' ? 'green' : ($entry['attendance_status'] === 'absent' ? 'red' : 'yellow')) ?></td>
		<td><form method="post" action="<?= e(site_url('admin/absensi/'.(int) $entry['id'])) ?>" class="row wrap"><?= csrf_field() ?><input type="hidden" name="attendance" value="present"><input type="hidden" name="lomba" value="<?= e($f_lomba ?? '') ?>"><input type="hidden" name="desa" value="<?= e($f_desa ?? '') ?>"><input type="hidden" name="status" value="<?= e($f_status ?? '') ?>"><input type="hidden" name="nama" value="<?= e($f_nama ?? '') ?>"><button class="btn primary sm" type="submit"<?= $entry['attendance_status'] === 'present' ? ' disabled' : '' ?>>Hadir</button></form><form method="post" action="<?= e(site_url('admin/absensi/'.(int) $entry['id'])) ?>" class="row wrap mt"><?= csrf_field() ?><input type="hidden" name="attendance" value="absent"><input type="hidden" name="lomba" value="<?= e($f_lomba ?? '') ?>"><input type="hidden" name="desa" value="<?= e($f_desa ?? '') ?>"><input type="hidden" name="status" value="<?= e($f_status ?? '') ?>"><input type="hidden" name="nama" value="<?= e($f_nama ?? '') ?>"><button class="btn danger sm" type="submit"<?= $entry['attendance_status'] === 'absent' ? ' disabled' : '' ?>>Tidak hadir</button></form></td></tr><?php endforeach; ?>
	</tbody></table></div><?php else: ?><p class="sub">Tidak ada peserta yang cocok dengan filter.</p><?php endif; ?>
</div>
