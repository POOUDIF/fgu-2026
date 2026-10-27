# Implementasi CodeIgniter 3 — port fitur FGU 3.0

CI3 mempertahankan pola controller/service/view dan tampilan portal yang sudah ada. Fitur serta aturan
bisnis terbaru diselaraskan dengan `fgu-app-main`; revisi juklak Oktober 2026 menjadi acuan data lomba.
Prototipe `FGU-Fixed-Juknis.html` tetap menjadi referensi visual bila tidak bertentangan dengan perilaku terbaru.

## 1. Pemetaan arsitektur

| Konsep | CodeIgniter 3 |
|---|---|
| Halaman | Controller method + View (`application/views`, 3 layout: public / auth / app) |
| Aksi tulis | Controller POST → Service → redirect (Post/Redirect/Get) + flash/toast |
| Hasil aksi | `App\Support\ActionResult` |
| Penjaga rute | `Auth_Controller` (login) + `Village_/Judge_/Admin_Controller` (peran) |
| Autentikasi | `Auth_service` + sesi CI3 (driver `database`, tabel `ci_sessions`), bcrypt |
| Otorisasi data | Pemeriksaan peran + kepemilikan di controller/service (desa hanya miliknya, juri hanya lomba yang ditugaskan) |
| Aturan bisnis | `application/libraries/*_service.php` (transaksi) + kelas murni `App\Domain\*` (diuji PHPUnit) |
| Perhitungan hasil | `Result_model` memakai `App\Domain\Scoring` |
| Ekspor | PhpSpreadsheet (`Excel_exporter`), dibatasi `Export_guard` |
| Interaksi UI | `public/assets/js/app.js` (tanpa dependensi): `<dialog>`, baris dinamis lewat `<template>`, isi form dari `data-fill` |

### Peran dan menu

| Peran | Menu |
|---|---|
| Admin Daerah / Super Admin | Dashboard, Kontrol Lomba, Kontrol Penilaian, Daftar Desa, Daftar Lomba, Pendaftaran Lomba, Hasil Penilaian, Daftar Pemenang, Kelola Akun, Kelola Homepage, Pengumuman |
| Admin Desa | Dashboard, Kontrol Lomba, Kontrol Nilai, Daftar Lomba (pendaftaran), Data & Foto Peserta, Link Karya, Hasil |
| Juri | Dashboard, Kontrol Lomba, Kontrol Penilaian, Lomba Saya, Penilaian, Penilaian Tersimpan |
| Admin Lomba | Ringkasan dan review/input/koreksi penilaian hanya untuk lomba yang ditugaskan; seluruh perubahan dicatat di audit |

Rute lengkap: [application/config/routes.php](../application/config/routes.php). Aksi tulis memakai POST ke sub-path
(mis. `POST /admin/lomba/12/hapus`); kiriman karya online dari publik: `POST /karya`.

## 2. Model data & aturan bisnis

- **Lomba** (`competitions`): 16 lomba, mode pendaftaran `participant` atau `open`, jenjang/kelas/gender,
  kuota, format individu/regu/tim, metode pengumpulan dan skor, ketentuan, jumlah pemenang, serta status publikasi hasil.
  **Kriteria** (`criteria`): nama, bobot, dan nilai maks.
- **Peserta** (`participants`): nama unik per desa, nama orang tua, gender, jenjang, kelas, usia lama (tidak ditampilkan di form pendaftaran), serta path foto privat.
  **Pendaftaran** (`entries`) dapat menyimpan nama entry, username IG, jenis individu/tim, catatan anggota,
  tautan karya, status, dan sumber `village` atau `public`, termasuk tanpa baris peserta.
- Lomba **perorangan**: satu *entry* per peserta. Lomba **regu**: satu *entry* per desa berisi seluruh anggota.
- Simpan pendaftaran desa bersifat **ganti-seluruhnya** per lomba (seperti dialog prototipe); ditolak bila sudah ada nilai.
- **Data & Foto Peserta** Admin Desa merangkum entry semua lomba milik desanya, termasuk lomba pendaftaran langsung seperti Mewarnai yang tidak memakai baris `participants`. Entry langsung dan pendaftaran Bazaar Desa dapat diubah selama pendaftaran dibuka dan belum dinilai; kepemilikan desa diperiksa ulang saat menyimpan.
- Validasi (`App\Domain\RegistrationInput`): nama wajib, kuota, nama kembar dalam satu desa, tautan hanya untuk lomba online dan berformat URL.
- **Nilai juri** terkunci setelah disimpan (sekali kirim; `UNIQUE scores_one_per_criterion`); nilai per kriteria minimal 50 dan tidak melebihi nilai maksimum divalidasi di server.
- **Rumus**: skor juri berbobot = `100 × Σ(nilai/nilai maks × bobot) / Σ(bobot)`; lomba Cerdas Cermat memakai
  satu papan nilai bersama, bukan rata-rata antarjuri. Empat babak tersimpan langsung; poin babak yang belum dimainkan
  bernilai 0, pengurangan indisipliner tercatat per babak beserta alasan, dan peringkat sementara memakai total bersih.
  Nilai lomba lain tetap dihitung sebagai rata-rata skor juri − penalti.
  Peringkat memakai *competition ranking* (seri berbagi peringkat); `disqualified` tidak diperingkat; pemenang = `winner_count` teratas.
- **Koreksi / reset juri**: hanya Super Admin, alasan wajib, tercatat di `score_audit_log` dalam transaksi yang sama.
- **Admin Lomba**: Admin Daerah menugaskan akun ke satu atau beberapa lomba; akun dapat melengkapi nilai juri yang terlewat atau mengoreksi nilai pada lomba yang ditugaskan, dengan alasan wajib dan audit per kriteria.
- **Cerdas Cermat**: migrasi `20261025000001` menambahkan status babak bersama dan nilai setiap entry per ronde. Juri memakai tombol poin Babak 1–4 dan Lanjut/Kembali; lanjut menyimpan nilai yang belum disentuh sebagai 0, sedangkan Selesai mengunci papan.
- Admin Daerah tak boleh mengelola akun Super Admin (`App\Domain\Role::canManage`).

## 3. Keputusan & penyimpangan

### Dari prototipe (sengaja)

| Prototipe | Implementasi | Alasan |
|---|---|---|
| Data di `localStorage`, tanpa login | Akun nyata (Admin Desa / Juri / Admin Daerah), sesi DB, bcrypt | Prototipe hanya demo; spesifikasi awal mewajibkan peran |
| Nama juri teks bebas | Juri = akun, ditugaskan ke lomba lewat Kelola Akun / Daftar Lomba | Penilaian harus terikat identitas |
| Lomba beregu tampil per peserta | Satu *entry* regu per desa; dinilai sebagai satu kesatuan | Sesuai juknis (satu regu = satu penampilan) |
| Mode penilaian "Bebas" | Dihapus; selalu jumlah seluruh kriteria | Prototipe final memakai jumlah |
| Nilai tersimpan di browser | Tabel `scores`, terkunci, koreksi hanya admin + audit | Integritas data |
| Kiriman publik tanpa batas | Pembatas laju (`Rate_limiter`) | Anti-spam |
| Nama ikon bebas | Dipetakan ke berkas `public/assets/icons/*.png` (Tafsir → khotbah-jumat, Karya Tulis → kaligrafi, Dakwah → mc); `business-plan`, `pr-13`, `puzzle` tidak dipakai | Folder ikon tidak memuat nama yang persis sama |

### Padanan perubahan terbaru dari aplikasi Next.js

- **Foto peserta:** Foto wajib saat pendaftaran peserta baru. Admin Desa mengunggah JPG/PNG/WebP hingga 2 MB.
  File berada di `writable/uploads/participants/`, di luar document root; URL gambar memeriksa sesi,
  peran, dan kepemilikan desa sebelum mengirim file. Path foto dipertahankan saat pendaftaran peserta
  disimpan ulang untuk nama peserta yang sama.
- **Publikasi hasil:** Super Admin/Admin Daerah dapat memublikasikan atau menarik publikasi per lomba
  di Daftar Pemenang. Hasil desa dan pemenang beranda hanya menampilkan lomba terpublikasi; penilaian
  juri dikunci setelah publikasi, termasuk pemeriksaan ulang dalam transaksi.
- **Dashboard live:** Penilaian terbaru diperbarui melalui polling endpoint admin tiap 15 detik. Ini
  padanan CI3 untuk feed Realtime Supabase; tidak memakai WebSocket.
- **Skema:** Migrasi 4 menambahkan `participants.photo_path` dan `competitions.results_published`.
  Migrasi 5 menyegarkan seluruh teks ketentuan dari [database/juklak_2026_rules.json](../database/juklak_2026_rules.json),
  hasil port dari revisi konten Next.js. Email akun sudah tersimpan langsung di tabel `users` CI3;
  kolom dan view PostgreSQL khusus tidak diperlukan.
- **Admin Lomba:** Migrasi `20261013000001` menambahkan peran dan penugasan lomba Admin Lomba serta aksi audit input/koreksi nilai.

### Dipertahankan dari spesifikasi awal

Jadwal buka/tutup pendaftaran (opsional, diatur di Kelola Homepage; kosong = selalu terbuka), sandi awal acak + wajib ganti, pembatasan login 5 gagal/15 menit, ekspor Excel (anti formula injection),
penalti, koreksi nilai dengan audit, zona waktu WIB, jumlah desa dari DB.

## 4. Keamanan yang diterapkan

- **CSRF** aktif global (`csrf_protection`); **XSS**: semua output lewat `e()`, tanpa inline `<script>`, CSP `script-src 'self'`.
- **Header**: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP.
- **SQL injection**: query builder / *bind*; **IDOR**: pemeriksaan kepemilikan tiap query desa/juri; **open redirect**: `back()` hanya path internal.
- **Sesi**: `HttpOnly`, `SameSite=Lax`, `Secure` via `COOKIE_SECURE`; regenerasi saat login.
- **Ekspor Excel**: `CellSanitizer`, otorisasi admin, pembatas laju, `no-store`.
- **Konfigurasi**: rahasia hanya di `.env`; `db_debug = FALSE`; error produksi tidak ditampilkan.

## 5. Batasan

1. **CI3 + PHP ≥ 8.2**: peringatan *dynamic property* disembunyikan; CI3 tidak lagi menerima fitur baru. Pertimbangkan CI4 untuk jangka panjang.
2. Feed penilaian memakai polling 15 detik, bukan Realtime push. Foto hanya dikelola oleh Admin Desa.
3. Tidak ada reset sandi mandiri (tanpa email); reset dilakukan admin dari Kelola Akun.
4. Penalti tidak punya log audit sendiri (hanya koreksi nilai/reset juri).
5. Tes otomatis: PHPUnit untuk logika murni; alur DB/HTTP diuji lewat skrip E2E terpisah (tidak ada di repo).
6. Hasil dihitung ulang tiap request, tanpa cache (skala 9 desa).
