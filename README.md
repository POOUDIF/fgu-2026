# FGU 3.0 — Festival Generasi Unggul (Bekasi Barat, 2026) · CodeIgniter 3

Aplikasi pendaftaran, penilaian, dan hasil lomba FGU 3.0 di atas **CodeIgniter 3.1.13 + MySQL**.
Tampilan dan alur mengikuti prototipe `FGU-Fixed-Juknis.html`; spesifikasi awal ada di
[docs/DOKUMENTASI.md](docs/DOKUMENTASI.md). Pemetaan fitur, keputusan, dan penyimpangan dari prototipe
ada di [docs/IMPLEMENTASI-CI3.md](docs/IMPLEMENTASI-CI3.md).

## Persyaratan

| Komponen | Versi |
|---|---|
| PHP | 8.1 – 8.3 (ekstensi: `mysqli`, `mbstring`, `fileinfo`, `zip`, `xml`, `gd` — dua terakhir untuk PhpSpreadsheet) |
| MySQL / MariaDB | MySQL 8.0.16+ atau MariaDB 10.5+ (butuh `CHECK` constraint & tipe `JSON`) |
| Composer | 2.x |

> CodeIgniter 3.1.13 belum resmi mendukung PHP ≥ 8.2 (muncul peringatan *dynamic property*).
> Aplikasi menyembunyikan `E_DEPRECATED`; **PHP 8.1 paling aman**.

## Instalasi

```bash
composer install                      # vendor/: PhpSpreadsheet, phpdotenv, PHPUnit
cp .env.example .env                  # lalu isi BASE_URL, DB_*, ENCRYPTION_KEY
php -r "echo bin2hex(random_bytes(32));"   # salin ke ENCRYPTION_KEY

# buat database kosong (utf8mb4), mis.:
#   CREATE DATABASE fgu_2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

php public/index.php cli migrate        # skema + data juknis: 9 desa, 16 lomba (ketentuan, kriteria, ikon), konten beranda
php public/index.php cli create_admin   # interaktif: email, nama, sandi (kosong = acak)

php -S localhost:8080 -t public         # server pengembangan → http://localhost:8080
```

Produksi: cara yang disarankan adalah mengarahkan *document root* web server ke folder **`public/`** saja
(`system/`, `application/`, `writable/`, `.env`, `vendor/` tidak boleh terjangkau dari web).
Contoh Nginx: `try_files $uri $uri/ /index.php/$request_uri;`. Apache memakai `public/.htaccess`.

Jika hosting harus memakai folder proyek sebagai *document root* (misalnya
`https://domain.example/fgu-2026/`), tersedia `index.php` dan `.htaccess` di root.
Atur `BASE_URL` di `.env` ke URL folder tersebut, dengan garis miring di akhir. Root
`.htaccess` meneruskan URL aset ke `public/assets` dan membatasi akses ke direktori internal;
URL lama yang berakhir dengan `/public` dialihkan ke root aplikasi. Pastikan Apache
mengaktifkan `mod_rewrite` dan mengizinkan aturan `.htaccess` (`AllowOverride`).

### Setelah instalasi

1. Login sebagai Super Admin (sandi wajib diganti saat login pertama).
2. **Kelola Homepage**: tagline, tentang, tanggal & lokasi acara. **Daftar Desa**: sesuaikan nama desa / PIC.
3. **Daftar Lomba**: ketentuan, kuota, kriteria, gender peserta, dan juri tiap lomba sudah terisi dari juknis; ubah bila perlu. Pendaftaran menolak gender yang tidak sesuai.
4. **Kelola Akun** → buat akun Admin Desa (pilih desa), Juri, dan Admin Lomba; tugaskan Juri dan Admin Lomba ke lomba yang sesuai.
5. Setel `COOKIE_SECURE=true` di `.env` bila memakai HTTPS (wajib di produksi).

Data lomba awal bersumber dari [database/juknis_2026.json](database/juknis_2026.json) (dibaca migrasi kedua).
Ikon lomba ada di `public/assets/icons/` (nama berkas disimpan di kolom `competitions.icon`).

Migrasi versi 3 menyelaraskan pendaftaran open, data kelas/gender, bobot dan metode penilaian, serta konten
juklak revisi. Migrasi versi 4 menambahkan publikasi hasil dan path foto peserta privat; migrasi versi 5
menyelaraskan isi lengkap ketentuan 16 lomba dengan revisi terbaru; migrasi versi 6 menambahkan verifikasi
absensi, denah dan rundown acara, serta pendaftaran Bazaar UMKM dan Bazaar Desa. Migrasi `20261013000001`
menambahkan peran Admin Lomba dan penugasan lomba. Migrasi `20261013000002` menambahkan penyimpanan draft
nilai juri yang terpisah dari nilai final. Juri dapat memeriksa dan mengubah draft peserta, lalu submit seluruh
nilai lomba setelah semua peserta hadir sudah dinilai. Migrasi `20261013000003` menambahkan pengaturan buka/tutup
pendaftaran per lomba oleh Super Admin dan menghubungkan pendaftaran Bazaar Desa ke form penilaian lomba;
juri dan kriteria Bazaar Desa dikelola dari Daftar Lomba. Database CI3 yang sudah berjalan perlu menjalankan
`php public/index.php cli migrate latest` sebelum versi aplikasi ini digunakan.
Migrasi `20261013000004` menghapus duplikasi Lomba Stand Bazaar: pendaftaran lomba Bazaar Desa menggunakan
form khusus Bazaar Desa, sedangkan Bazaar UMKM tetap memakai formulir pendaftaran publik.
Migrasi `20261013000005` menambahkan pencatatan penalti indisipliner per babak untuk Cerdas Cermat; poin
benar/salah dicatat langsung dari halaman juri dan penalti dengan alasan langsung mengurangi hasil akhir.
Migrasi `20261015000001` memperjelas batas gender dan jenjang tiap lomba serta menyimpan gender peserta pada
pendaftaran terbuka. Dokumen juklak lokal disajikan melalui menu Lomba; denah yang diunggah Super Admin
ditampilkan pada bagian khusus di beranda. Tata letak publik dan portal menyesuaikan layar ponsel/tablet.
Migrasi `20261015000002` menambahkan kontrol buka/tutup pendaftaran Bazaar UMKM khusus Super Admin;
pendaftaran dikunci secara default dan validasi server tetap menolak kiriman ketika ditutup.
Migrasi `20261015000003` memisahkan jenis peserta dari ketentuan gender dan menghapus keterangan umur
dari label kategori lomba. Migrasi `20261015000004` menetapkan jumlah peserta Praktek Sholat Berjamaah
sebanyak 5–10 orang per desa dan menambahkan pengaturan jumlah minimum pada Daftar Lomba.
Migrasi `20261025000001` menambahkan papan nilai bersama Cerdas Cermat dengan empat babak, nilai per
desa yang tersimpan langsung, dan status babak aktif/selesai. Jalankan `php public/index.php cli migrate latest`
sebelum menggunakan alur penilaian Cerdas Cermat yang baru.

## Perintah

| Perintah | Fungsi |
|---|---|
| `composer test` | Menjalankan PHPUnit (unit test logika domain murni) |
| `composer lint` | `php -l` untuk seluruh berkas PHP |
| `php public/index.php cli migrate [versi\|latest]` | Menjalankan migrasi |
| `php public/index.php cli create_admin [email%40x.com Nama%20Lengkap [sandi]]` | Membuat Super Admin (tanpa argumen = interaktif; argumen harus URL-encode) |

## Struktur

```
public/                  # document root: index.php, .htaccess, assets/{css,js,img,icons}
application/
  config/                # config.php (env-driven), routes.php, database.php, migration.php
  core/                  # MY_Controller → Auth_Controller → Village|Judge|Admin_Controller
  controllers/           # Home, Auth, Account, Dashboard, Cli + desa/ juri/ admin/
  models/                # akses data (query builder, tanpa logika tampilan)
  libraries/             # *_service (aturan bisnis + transaksi), Excel_exporter, Rate_limiter, Export_guard
  src/                   # kelas PHP murni ber-namespace App\ (PSR-4): Domain/ dan Support/ — diuji PHPUnit
  migrations/            # skema & data referensi (timestamp)
  views/                 # layouts/, partials/, public/, desa/, juri/, admin/
database/juknis_2026.json # data awal desa, lomba, kriteria, konten beranda
writable/                # log, cache
tests/                   # PHPUnit
```

## Status verifikasi

Dijalankan nyata di PHP 8.3 + MySQL 8.0.34:

- PHPUnit: 30 tes (logika domain & pendukung) lulus.
- Skrip E2E HTTP (admin, admin desa, juri, publik): 139 pemeriksaan lulus — pendaftaran, kuota, nama kembar,
  kiriman karya publik, penilaian terkunci, akumulasi + penalti, koreksi + audit, ekspor Excel, throttle login.
- Uji antarmuka dengan Playwright (Brave) desktop & mobile: dialog, baris dinamis, sidebar, tanpa *console error*,
  tanpa *overflow* horizontal. Pengecekan di perangkat sungguhan tetap disarankan.

## Stack lokal tanpa instalasi sistem (macOS)

Untuk mesin tanpa PHP/MySQL, PHP statis, MySQL 8.0.34, dan Composer dapat ditaruh di `.local/`
(diabaikan git). Setelah terpasang: `./.local/start.sh` (MySQL :3307 + web :8080) dan `./.local/stop.sh`.
Pakai `-d disable_functions=curl_multi_init,curl_init` pada `composer.phar` bila `curl` PHP statis mogok (crash HTTP/3).
