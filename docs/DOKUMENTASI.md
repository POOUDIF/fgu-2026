# Dokumentasi Teknis FGU 3.0 (Festival Generasi Unggul)

Dokumen ini menjelaskan arsitektur, frontend, dan backend aplikasi **fgu-app**. Isinya disusun dari
kode sumber di repositori ini (commit terakhir `5ecd356`).

> **Catatan keterbatasan.** File skema database `fgu_01_schema.sql` dan `fgu_02_seed.sql`
> **tidak ada di repositori** (hanya `fgu_03` dan `fgu_04`). Karena itu, bagian
> [Database](#6-database-supabase-postgres) disimpulkan dari cara kode memakainya (nama tabel,
> kolom, view, RPC). Bagian yang bertanda **(disimpulkan)** perlu dicocokkan dengan skema asli di
> Supabase. Disarankan memasukkan kedua file SQL tersebut ke repositori.

---

## Daftar Isi

1. [Gambaran umum](#1-gambaran-umum)
2. [Tech stack & struktur proyek](#2-tech-stack--struktur-proyek)
3. [Setup & menjalankan](#3-setup--menjalankan)
4. [Arsitektur & alur data](#4-arsitektur--alur-data)
5. [Autentikasi & otorisasi](#5-autentikasi--otorisasi)
6. [Database (Supabase/Postgres)](#6-database-supabase-postgres)
7. [Backend: Server Actions](#7-backend-server-actions)
8. [Backend: Route Handlers](#8-backend-route-handlers)
9. [Frontend: peta halaman (routes)](#9-frontend-peta-halaman-routes)
10. [Frontend: komponen](#10-frontend-komponen)
11. [Library internal (`src/lib`)](#11-library-internal-srclib)
12. [Fitur kunci secara mendalam](#12-fitur-kunci-secara-mendalam)
13. [Styling](#13-styling)
14. [Deploy](#14-deploy)
15. [Keamanan: yang sudah ada & catatan perbaikan](#15-keamanan-yang-sudah-ada--catatan-perbaikan)
16. [Konvensi kode](#16-konvensi-kode)

---

## 1. Gambaran umum

Aplikasi web untuk **Festival Generasi Unggul 3.0 (Bekasi Barat, 2026)**: pendaftaran peserta per
desa, daftar mata lomba, penilaian juri, dashboard live untuk panitia, serta hasil dan pemenang.

### Peran pengguna

| Peran (`role`) | Label UI | Area | Kemampuan |
|---|---|---|---|
| *(tanpa login)* | Publik | `/`, `/lomba/[slug]` | Melihat lomba, kriteria, jadwal, dan pemenang yang sudah dipublikasikan |
| `village_admin` | Admin Desa | `/desa/*` | Mengelola peserta desanya, mendaftarkan ke lomba, mengisi tautan karya, melihat hasil desanya |
| `judge` | Juri | `/juri/*` | Menilai pendaftaran pada lomba yang ditugaskan. Nilai terkunci setelah dikirim |
| `regional_admin` | Admin Daerah | `/admin/*` | Dashboard live, rekap peserta, diskualifikasi, publikasi hasil, kelola akun juri/desa, pengaturan acara |
| `super_admin` | Super Admin | `/admin/*` | Semua kemampuan Admin Daerah **ditambah**: koreksi nilai, reset input juri, penalti, kelola akun admin |

### Prinsip desain utama

- **Aturan bisnis ditegakkan di database** (trigger, constraint, RLS), bukan hanya di UI. Kode
  frontend hanya melakukan *pre-filter* dan validasi ramah pengguna.
- **Tidak ada backend terpisah.** "Backend" = Supabase (Postgres + Auth + Storage + Realtime) ditambah
  Server Actions dan Route Handlers Next.js.
- **Tidak memakai `service_role` key.** Semua query berjalan sebagai pengguna yang login
  (publishable key + sesi cookie), sehingga RLS selalu berlaku. Operasi istimewa (buat/hapus akun,
  koreksi nilai) lewat fungsi RPC `security definer` di database.

---

## 2. Tech stack & struktur proyek

| Lapisan | Teknologi |
|---|---|
| Framework | Next.js **16.3.8** (App Router, Server Components, Server Actions, `proxy.ts`) |
| UI | React **19.2.8**, CSS global murni (`globals.css`), tanpa library UI/Tailwind |
| Bahasa | TypeScript 5 (strict, alias `@/*` → `src/*`) |
| Backend as a Service | Supabase: `@supabase/ssr` ^0.12, `@supabase/supabase-js` ^2.117 |
| Ekspor | `exceljs` ^4.4 |
| Lint | ESLint 9 + `eslint-config-next` |
| Runtime | Node.js ≥ 20.9 |

> `AGENTS.md` mengingatkan bahwa versi Next.js ini memiliki *breaking changes*. Dokumentasi resminya
> ada di `node_modules/next/dist/docs/`. Contoh yang sudah terlihat di kode: `middleware` berganti
> nama menjadi **`proxy.ts`**, `params`/`searchParams` bertipe **`Promise`** dan harus di-`await`.

### Struktur direktori

```
fgu-app/
├── fgu_03_profile_email.sql        # migrasi: kolom email di profiles + view live_submissions
├── fgu_04_participant_photo.sql    # migrasi: kolom photo_path di participants
├── public/icons/                   # logo-fgu.png + ikon per mata lomba (PNG)
└── src/
    ├── proxy.ts                    # penjaga rute + penyegar sesi (pengganti middleware)
    ├── app/
    │   ├── layout.tsx              # layout root: Nav + <main> + footer
    │   ├── globals.css             # seluruh styling
    │   ├── page.tsx                # beranda publik
    │   ├── login/page.tsx
    │   ├── dashboard/page.tsx      # pengarah ke beranda sesuai peran
    │   ├── lomba/[slug]/page.tsx   # detail lomba publik
    │   ├── auth/signout/route.ts   # POST: keluar
    │   ├── actions/                # Server Actions: auth, admin, desa, juri
    │   ├── admin/                  # area panitia (layout + 5 halaman + 2 route ekspor)
    │   ├── desa/                   # area Admin Desa
    │   └── juri/                   # area Juri
    ├── components/                 # komponen React (kebanyakan "use client")
    └── lib/                        # auth, tipe, util, foto, excel, klien Supabase
```

---

## 3. Setup & menjalankan

### Prasyarat
Node.js ≥ 20.9 dan project Supabase.

### Langkah

1. **Database.** Di *Supabase → SQL Editor*, jalankan berurutan:
   `fgu_01_schema.sql` → `fgu_02_seed.sql` → `fgu_03_profile_email.sql` → `fgu_04_participant_photo.sql`.
   (Dua file pertama tidak ada di repo, lihat catatan di atas.)
2. **Storage.** Buat bucket **`participant-photos`**: *private*, batas 5 MB, MIME `image/jpeg|png|webp`.
   Tambahkan kebijakan `storage.objects` agar Admin Desa boleh *insert/update/delete* hanya di folder
   `{village_id}/` miliknya, dan pengguna terautentikasi boleh membaca (dibutuhkan untuk signed URL).
3. **Env.** Salin `.env.example` menjadi `.env.local`:

   | Variabel | Wajib | Keterangan |
   |---|---|---|
   | `NEXT_PUBLIC_SUPABASE_URL` | ya | URL project Supabase |
   | `NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY` | ya | Publishable (anon) key. **Jangan** isi `service_role` |
   | `SUPABASE_IMAGE_TRANSFORM` | tidak | `1` untuk memakai thumbnail Storage (fitur plan Pro). Dibaca di `photoUrl.ts`, tidak ada di `.env.example` |

4. **Auth.** Matikan *Allow new users to sign up* di *Authentication → Sign In / Providers*.
5. `npm install` lalu `npm run dev` → http://localhost:3000
6. Buat akun pertama lewat *Supabase Dashboard → Authentication → Users → Add user* (centang *Auto
   Confirm*), lalu ubah `role` di tabel `profiles` menjadi `super_admin`. Akun lain dibuat dari UI
   `/admin/akun`.

### Skrip npm

| Perintah | Fungsi |
|---|---|
| `npm run dev` | Server pengembangan |
| `npm run build` | Build produksi |
| `npm start` | Menjalankan hasil build |
| `npm run lint` | ESLint |

Belum ada skrip/test otomatis (unit, integrasi, e2e).

---

## 4. Arsitektur & alur data

```
Browser ──► proxy.ts (segarkan sesi, redirect ke /login bila belum login di rute terproteksi)
   │
   ├─► Server Component (page.tsx) ──► Supabase (sesi user, RLS) ──► render HTML
   │
   ├─► Client Component (form) ──► Server Action ("use server") ──► Supabase (tabel / RPC)
   │                                         └─ revalidatePath() → halaman di-render ulang
   │
   ├─► Browser langsung ke Supabase Storage (upload foto peserta)
   ├─► Browser langsung ke Supabase Realtime (LiveFeed admin)
   └─► GET /admin/*/export ──► Route Handler ──► ExcelJS ──► unduhan .xlsx
```

Pola yang dipakai konsisten di seluruh aplikasi:

- **Baca data** = Server Component yang `await`-kan beberapa query Supabase secara paralel
  (`Promise.all`), lalu dirender. Tidak ada fetch dari client kecuali LiveFeed.
- **Tulis data** = Server Action yang menerima `FormData` dan mengembalikan `ActionState`
  (`{ ok?, error?, message? }`). Setelah sukses memanggil `revalidatePath`.
- **Otorisasi berlapis**: (1) `proxy.ts` untuk login, (2) `requireRole`/`requireAdmin` di layout/page,
  (3) pemeriksaan peran di awal setiap Server Action, (4) **RLS di database sebagai penjaga utama**.

---

## 5. Autentikasi & otorisasi

### 5.1 Sesi (Supabase Auth + cookie)

- Login: email + kata sandi (`signInWithPassword`). Tidak ada registrasi mandiri.
- Sesi disimpan di cookie oleh `@supabase/ssr`. Tiga klien Supabase:

| File | Dipakai di | Catatan |
|---|---|---|
| `lib/supabase/server.ts` | Server Component, Action, Route Handler | Membaca/menulis cookie via `next/headers`. `setAll` ditelan error-nya bila dipanggil dari Server Component (sesi disegarkan oleh proxy) |
| `lib/supabase/client.ts` | Client Component | Dipakai untuk upload foto dan Realtime |
| `lib/supabase/session.ts` | `proxy.ts` | `updateSession()` menyegarkan token dan memanggil `auth.getClaims()` |

### 5.2 `src/proxy.ts`

- Berjalan di hampir semua request (matcher mengecualikan `_next/static`, `_next/image`,
  `favicon.ico`, dan berkas gambar).
- Rute terproteksi: `/dashboard`, `/desa`, `/juri`, `/admin` (beserta turunannya). Bila belum login →
  redirect ke `/login`.
- **Hanya memeriksa "sudah login atau belum"**, bukan peran. Pengecekan peran dilakukan di layout.

### 5.3 Helper peran (`src/lib/auth.ts`)

| Fungsi | Perilaku |
|---|---|
| `getCtx()` | Mengembalikan `{ supabase, user, profile }`. `user` dari `auth.getUser()`, `profile` dari tabel `profiles` |
| `isAdmin(role)` | `super_admin` atau `regional_admin` |
| `isSuper(role)` | Hanya `super_admin` |
| `homeFor(role)` | admin → `/admin`, `village_admin` → `/desa`, selain itu → `/juri` |
| `requireRole(role)` | Wajib login dan role persis sama; selain itu `redirect` |
| `requireAdmin()` | Wajib `super_admin` atau `regional_admin` |

### 5.4 Matriks hak akses

| Aksi | Publik | Juri | Admin Desa | Admin Daerah | Super Admin |
|---|:--:|:--:|:--:|:--:|:--:|
| Lihat beranda, lomba, pemenang terpublikasi | ✓ | ✓ | ✓ | ✓ | ✓ |
| Kirim nilai | | ✓ (lomba tugasnya) | | | |
| CRUD peserta & pendaftaran desanya | | | ✓ (saat pendaftaran dibuka) | | |
| Isi/ubah tautan karya | | | ✓ (saat buka / sebelum deadline) | | |
| Diskualifikasi pendaftaran | | | | ✓ | ✓ |
| Publikasi hasil, ubah jadwal & konten beranda | | | | ✓ | ✓ |
| Buat akun juri / Admin Desa, reset sandi, hapus akun non-admin | | | | ✓ | ✓ |
| Buat akun Admin Daerah; kelola akun admin | | | | ✗ | ✓ |
| Tambah/hapus penalti | | | | ✗ | ✓ |
| Koreksi nilai, reset input juri | | | | ✗ | ✓ |
| Ekspor Excel | | | | ✓ | ✓ |

Aturan khusus di `updateProfile`: tidak boleh mengubah peran akun sendiri; Admin Daerah tidak boleh
memberi peran admin maupun menyentuh akun admin; `village_admin` wajib punya `village_id`.

---

## 6. Database (Supabase/Postgres)

> Bagian ini **disimpulkan dari kode**, kecuali `live_submissions` dan kolom `profiles.email` /
> `participants.photo_path` yang SQL-nya ada di repo.

### 6.1 Tabel

| Tabel | Kolom yang terlihat di kode | Keterangan |
|---|---|---|
| `profiles` | `id` (= `auth.users.id`), `full_name`, `email`, `role`, `village_id`, `created_at` | Dibuat otomatis oleh trigger `handle_new_user` saat user Auth dibuat (menyimpan nama dari `raw_user_meta_data.full_name` dan email) |
| `villages` | `id`, `name`, `sort_order` | Sembilan desa se-Bekasi Barat (UI admin memakai angka 9) |
| `events` | `id`, `name`, `event_date`, `venue`, `theme`, `registration_opens_at`, `registration_closes_at`, `created_at` | Satu baris acara; kode selalu mengambil baris pertama (`order created_at limit 1`) |
| `site_content` | `key`, `value` | Pasangan kunci-nilai: `tagline`, `about`, `announcement` |
| `competitions` | lihat `Competition` di `lib/types.ts` | Mata lomba. Kolom penting di bawah |
| `competition_slots` | `id`, `competition_id`, `label`, `gender`, `level`, `ranking_group`, `sort_order` | Slot per desa (mis. "Putra", "Putri", "SD"). `ranking_group` dipakai untuk pemeringkatan terpisah |
| `criteria` | `id`, `competition_id`, `group_name`, `name`, `weight`, `max_score`, `sort_order` | Kriteria penilaian |
| `competition_judges` | `competition_id`, `judge_id` | Penugasan juri ke lomba |
| `participants` | `id`, `village_id`, `full_name`, `parent_name`, `gender` (L/P), `education_level`, `grade`, `age`, `photo_path` | Peserta milik satu desa |
| `entries` | `id`, `competition_id`, `village_id`, `slot_id`, `team_name`, `submission_url`, `status` (`registered`/`disqualified`), `disqualified_reason`, `created_at` | Satu pendaftaran desa pada satu lomba |
| `entry_members` | `entry_id`, `participant_id` | Anggota sebuah pendaftaran |
| `scores` | `id`, `entry_id`, `judge_id`, `criterion_id`, `score`, `updated_at` | Satu baris per juri × kriteria × pendaftaran |
| `penalties` | `id`, `entry_id`, `points`, `reason`, `created_at` | Pengurangan nilai (penalti) |
| `score_audit_log` | `changed_at`, `action` (`edit`/`reset_judge`), `competition_name`, `village_name`, `entry_label`, `judge_name`, `criterion_name`, `old_score`, `new_score`, `reason`, `changed_by_name` | Riwayat koreksi. Bisa tabel atau view |

**Kolom `competitions`** (dari `Competition`): `slug`, `name`, `cluster`, `schedule_type`
(`hari_h`/`pre_event`), `age_label`, `levels[]`, `grade_min`/`grade_max`, `max_age`,
`allowed_genders[]`, `participation_type` (`individual`/`team`), `team_size`, `composition_note`,
`max_entries_per_village`, `submission_mode` (`offline`/`online`), `submission_deadline`, `venue`,
`scoring_method` (`weighted_criteria`/`points`), `winner_count`, `results_published`, `rules[]`,
`sort_order`, `is_active`.

### 6.2 View

| View | Dipakai oleh | Isi |
|---|---|---|
| `live_submissions` *(SQL ada di `fgu_03`)* | Dashboard live, ekspor | 1 baris = 1 juri menilai 1 pendaftaran. Kolom: `entry_id`, `judge_id`, `submitted_at`, `criteria_count`, `total`, `competition_name`, `village_name`, `judge_name`, `entry_label`. `security_invoker = true`; di-`revoke` dari `anon`, `grant select` ke `authenticated` |
| `entry_results` | `/admin/hasil`, ekspor | Per pendaftaran: `avg_total`, `penalty`, `final_score`, `judge_count`, `expected_judges`, `rank_in_group`, `ranking_group`, `slot_label`, `entry_label`, `village_name` |
| `score_details` | `/admin/hasil/[slug]`, ekspor | Rincian per juri × kriteria: `score_id`, `score`, `max_score`, `weight`, `weighted_part`, `scoring_method`, dll. |
| `scoring_progress` | Dashboard, halaman juri | Per juri × lomba: `total_entries`, `scored_entries` |
| `public_winners` | Beranda publik | Pemenang lomba yang `results_published = true` |
| `published_results` | `/desa/hasil` | Hasil terpublikasi (di-RLS ke desa sendiri). Kolom `is_winner` |

### 6.3 Rumus nilai

- **Metode `weighted_criteria`** — total satu juri (0–100):
  `100 × Σ(score / max_score × weight) / Σ(weight)`
- **Metode `points`** (mis. Cerdas Cermat) — total = `Σ score`.
- **Nilai akhir** = rata-rata total semua juri − Σ penalti. Peringkat dihitung per `ranking_group`.
- Pratinjau di `ScoreForm` dan halaman penilaian juri memakai rumus yang sama dengan view.

### 6.4 Fungsi RPC (`security definer`)

| RPC | Parameter | Dipanggil dari | Hak |
|---|---|---|---|
| `submit_scores` | `p_entry_id`, `p_scores` (JSON `{criterion_id: nilai}`) | `submitScores` | Juri tertugas; menolak bila sudah pernah menilai atau hasil sudah dipublikasikan |
| `admin_create_account` | `p_email`, `p_password`, `p_full_name`, `p_role`, `p_village_id`, `p_competition_ids` | `createAccount` | Admin |
| `admin_reset_password` | `p_user_id`, `p_password` | `resetPassword` | Admin |
| `admin_delete_account` | `p_user_id` | `deleteAccount` | Admin |
| `admin_edit_score` | `p_score_id`, `p_new_score`, `p_reason` | `editScore` | Super Admin; menulis `score_audit_log` |
| `admin_reset_judge_entry` | `p_entry_id`, `p_judge_id`, `p_reason` | `resetJudgeEntry` | Super Admin; menghapus nilai juri, menulis audit |

### 6.5 Aturan yang ditegakkan database (menurut README & pesan error di kode)

Jenjang, kelas SD, batas usia, gender, slot, kuota per desa, satu peserta hanya satu lomba, dan
jadwal pendaftaran. Constraint yang namanya dikenali `dbError()`:

| Nama constraint | Pesan ramah |
|---|---|
| `entry_members_one_competition` | Peserta sudah terdaftar di lomba lain (satu peserta satu lomba) |
| `entries_one_per_slot` | Slot sudah terisi untuk desa tersebut |
| *(RLS)* `row-level security` | Tidak punya izin atau pendaftaran sudah ditutup |
| `duplicate key` | Data sudah ada |
| `violates foreign key` | Data masih dipakai data lain |

### 6.6 Storage & Realtime

- **Bucket `participant-photos`** (private). Path: `{village_id}/{participant_id}.{jpg|png|webp}`.
- **Realtime**: `LiveFeed` berlangganan `postgres_changes` pada tabel `scores` (publikasi Realtime
  harus aktif untuk tabel itu).

---

## 7. Backend: Server Actions

Semua berada di `src/app/actions/`, bertanda `"use server"`, menerima `FormData`, dan
mengembalikan `ActionState`. Pola umum: cek peran → validasi input → query/RPC → `revalidatePath`.

### 7.1 `auth.ts`

| Action | Input | Perilaku |
|---|---|---|
| `login` | `email`, `password` | `signInWithPassword`; gagal → "Email atau kata sandi salah."; sukses → `redirect("/dashboard")` |
| `changeOwnPassword` | `password` (≥ 6) | `auth.updateUser({ password })` untuk akun yang sedang login |

### 7.2 `admin.ts` (panitia)

Helper internal: `adminCtx()` (admin atau null) dan `superCtx()` (hanya Super Admin).
`refresh()` = `revalidatePath("/admin", "layout")`.

| Action | Hak | Input | Perilaku |
|---|---|---|---|
| `setEntryStatus` | Admin | `id`, `status` (`registered`/`disqualified`), `reason` | Update `entries` |
| `addPenalty` | Super | `entry_id`, `points` (> 0, koma desimal diterima), `reason` | Insert `penalties` |
| `deletePenalty` | Super | `id` | Hapus penalti |
| `setPublished` | Admin | `competition_id`, `publish` (`"true"`/`"false"`) | Update `competitions.results_published`; revalidate `/` |
| `updateProfile` | Admin | `id`, `full_name`, `role`, `village_id` | Aturan peran di §5.4; `village_id` di-null-kan bila bukan `village_admin` |
| `saveJudgeAssignments` | Admin | `judge_id`, `competition[]` | Diff terhadap `competition_judges`: insert yang baru, delete yang dicabut |
| `updateEvent` | Admin | `id`, `name`, `event_date`, `venue`, `registration_opens_at`, `registration_closes_at` | Waktu dari `datetime-local` dianggap WIB (UTC+7); buka harus < tutup |
| `updateSiteContent` | Admin | `tagline`, `about`, `announcement` | Upsert ke `site_content`; **nilai kosong diabaikan** (tidak menghapus konten) |
| `createAccount` | Admin (peran `regional_admin` hanya Super) | `email`, `password` (≥ 6), `full_name`, `role`, `village_id`, `competition[]` | RPC `admin_create_account`. Pesan sukses menampilkan email dan sandi awal |
| `resetPassword` | Admin (target admin hanya Super) | `id`, `password` (≥ 6) | RPC `admin_reset_password` |
| `deleteAccount` | Admin (target admin hanya Super) | `id` | Tidak boleh menghapus diri sendiri; RPC `admin_delete_account` |
| `editScore` | Super | `score_id`, `new_score` (≥ 0), `reason` (wajib) | RPC `admin_edit_score` |
| `resetJudgeEntry` | Super | `entry_id`, `judge_id`, `reason` (wajib) | RPC `admin_reset_judge_entry` |

### 7.3 `desa.ts` (Admin Desa)

`villageCtx()` mensyaratkan `role = village_admin` **dan** `village_id` terisi.

| Action | Perilaku |
|---|---|
| `createParticipant` | Validasi (`validateParticipant`), `id` dibuat di klien (UUID) agar foto bisa diunggah dulu; `photo_path` divalidasi harus `{villageId}/{id}.(jpg\|png\|webp)`; insert dengan `village_id` dari profil |
| `updateParticipant` | Foto boleh kosong bila peserta sudah punya foto. **Menolak** perubahan gender/jenjang/usia/kelas bila peserta sudah terdaftar di lomba. Bila ekstensi foto berubah, berkas lama dihapus (best-effort) |
| `deleteParticipant` | Menolak bila masih ada di `entry_members`; setelah hapus, foto di Storage ikut dihapus |
| `createEntry` | Cek lomba, `team_size` (jumlah anggota harus **tepat**), insert `entries` lalu `entry_members`. Bila insert anggota gagal, `entries` dihapus manual (kompensasi, bukan transaksi) |
| `deleteEntry` | Delete; bila 0 baris terhapus → dianggap jadwal sudah ditutup (RLS) |
| `updateSubmissionUrl` | Update `submission_url`; 0 baris → "Tautan tidak dapat diubah." |

### 7.4 `juri.ts`

`submitScores`: pastikan role `judge`; kumpulkan semua field berawalan `c_` (`c_{criterion_id}`),
tiap nilai wajib angka ≥ 0 (koma → titik); panggil RPC `submit_scores`; sukses → `redirect(/juri/{slug})`.
Batas atas `max_score` hanya dicek oleh atribut HTML `max` di form dan (diasumsikan) oleh RPC.

---

## 8. Backend: Route Handlers

| Method & path | File | Fungsi |
|---|---|---|
| `POST /auth/signout` | `app/auth/signout/route.ts` | `auth.signOut()` lalu redirect `303` ke `/` |
| `GET /admin/peserta/export` | `app/admin/peserta/export/route.ts` | Unduh rekap pendaftaran `.xlsx`. Query opsional `?lomba=<uuid>&desa=<uuid>` |
| `GET /admin/hasil/export` | `app/admin/hasil/export/route.ts` | Unduh rekap nilai seluruh lomba `.xlsx` |

Keduanya: `runtime = "nodejs"`, `dynamic = "force-dynamic"`, `maxDuration = 30`.

### Perlindungan ekspor (`lib/exportGuard.ts`)

- `authorizeExport()` — 401 bila belum login, 403 bila bukan admin, **429** bila pengguna yang sama
  mengekspor < 3 detik sejak ekspor sebelumnya (rate limit in-memory per instance server; peta dibersihkan
  bila > 200 entri).
- `fetchAll()` — memaginasi 1000 baris/request (batas PostgREST); query wajib punya urutan stabil.
- `UUID_RE` memvalidasi filter `lomba`/`desa` → 400 bila tidak valid.
- `xlsxResponse()` — nama berkas `FGU3-{prefix}-{YYYY-MM-DD}.xlsx` (tanggal WIB), `Cache-Control: no-store`.
- Error ditangkap, dicatat dengan `console.error`, dan dikembalikan sebagai JSON `{ error }` status 500.

### Isi workbook

**Ekspor peserta** — sheet `Pendaftaran` (1 baris per anggota: lomba, desa, slot, tim, nama, gender,
jenjang, kelas, umur, status, kelengkapan, tautan) dan `Ringkasan Desa` (jumlah pendaftaran aktif,
jumlah peserta unik, lomba terisi).

**Ekspor hasil** — sheet `Ringkasan`, satu sheet per lomba (peringkat, kelompok, desa, slot, peserta,
satu kolom per juri, rata-rata, pengurangan, nilai akhir, menang?), `Rincian Juri` (per kriteria), dan
`Riwayat Koreksi` (audit log).

### Util Excel (`lib/excel.ts`)

- `sanitizeCell` — **anti formula injection**: string berawalan `= + - @ \t` diberi awalan `'`.
- `sheetName` — membuang karakter terlarang, maks 31 karakter, dibuat unik.
- `addSheet` — header biru tebal, baris pertama dibekukan, auto-filter, lebar kolom otomatis (maks 50).

---

## 9. Frontend: peta halaman (routes)

Semua halaman adalah **Server Component** kecuali disebut lain. Query dijalankan paralel.

### 9.1 Publik

| Route | Isi |
|---|---|
| `/` | Hero + status pendaftaran, ringkasan juknis, kategori usia, kartu semua lomba aktif, alur, panduan pengumpulan karya (hanya bila ada lomba online), daftar pemenang. Data: `events`, `site_content`, `competitions`+`competition_slots`, `villages`, `public_winners`. Sebagian teks (aturan, kategori usia, alur) **hardcoded** di file |
| `/lomba/[slug]` | Detail lomba: kategori, jenis peserta, tempat, slot, deadline, ketentuan (`rules[]`), kriteria dikelompokkan per `group_name`. `generateMetadata` mengisi judul. 404 bila slug tak ada |
| `/login` | Form login (`ActionForm` + action `login`). Bila sudah login → redirect ke beranda perannya |
| `/dashboard` | Pengarah: belum login → `/login`; ada profil → `homeFor(role)`; login tanpa profil → pesan "Profil belum tersedia" + tombol keluar |

### 9.2 Admin Desa — `/desa` (layout: `requireRole("village_admin")`, sidebar)

| Route | Isi |
|---|---|
| `/desa` | Status pendaftaran, statistik, tabel status per lomba (Terisi / Sebagian / Belum daftar / Anggota belum lengkap) |
| `/desa/peserta` | Form tambah peserta + tabel peserta (foto thumbnail 40px lewat signed URL, lomba yang diikuti, Ubah, Hapus) |
| `/desa/peserta/[id]` | Form ubah peserta (`updateParticipant`) |
| `/desa/lomba` | Grid kartu lomba dengan hitungan terdaftar/kuota |
| `/desa/lomba/[slug]` | Detail: pendaftaran milik desa, form tautan karya (bila lomba online dan masih boleh), `EntryForm` untuk pendaftaran baru. Daftar peserta di form sudah **difilter** `isEligible` dan belum terdaftar. Bila akun belum punya desa, layout menampilkan pesan error |
| `/desa/hasil` | Hasil terpublikasi milik desa dari view `published_results` |

### 9.3 Juri — `/juri` (layout: `requireRole("judge")`)

| Route | Isi |
|---|---|
| `/juri` | Kartu lomba yang ditugaskan + bar progres |
| `/juri/[slug]` | Tabel pendaftaran terdaftar (status Sudah dinilai/Belum), tautan karya |
| `/juri/[slug]/[entryId]` | Bila belum dinilai: `ScoreForm`. Bila sudah: tabel nilai **terkunci** + total. Bila hasil sudah dipublikasikan dan belum dinilai: penilaian ditutup |

### 9.4 Panitia — `/admin` (layout: `requireAdmin()`, sidebar 5 menu)

| Route | Isi |
|---|---|
| `/admin` | Statistik (peserta, pendaftaran, penilaian masuk, progres %), `LiveFeed`, progres per lomba × juri |
| `/admin/peserta` | Rekap per desa, tabel pendaftaran dengan filter lomba/desa (GET query), tombol diskualifikasi, tombol ekspor |
| `/admin/hasil` | Per lomba: peringkat per kelompok, indikator juri masuk (`x/y`), penalti (`PenaltyForm`), tombol publikasi, tautan rincian, ekspor |
| `/admin/hasil/[slug]` | Rincian nilai per juri × kriteria (`<details>` per pendaftaran), koreksi/reset (Super Admin), 50 riwayat koreksi terbaru, tombol muat ulang |
| `/admin/akun` | Daftar akun (`ProfileForm`, `AccountActions`), dialog buat akun, penugasan juri. Admin Daerah melihat akun admin sebagai baris terkunci |
| `/admin/pengaturan` | `EventForm` (nama, tanggal, tempat, buka/tutup pendaftaran) dan `SiteContentForm` |

### 9.5 Layout root (`app/layout.tsx`)

`<html lang="id">`, `Nav` (Server Component, membaca profil), `<main>`, footer. Metadata judul
"Festival Generasi Unggul 3.0". Nav menampilkan tautan Dashboard dan `AccountMenu` bila login.

---

## 10. Frontend: komponen

Folder `src/components/`. Penanda: **[C]** = Client Component, **[S]** = Server Component.

### Form & aksi generik

| Komponen | Tipe | Fungsi |
|---|---|---|
| `ActionForm` + `SubmitButton` | C | Pembungkus form yang memanggil Server Action secara **imperatif** lewat `useTransition`, sehingga isian tidak ter-reset saat error (perilaku default React 19). Props: `action`, `resetOnSuccess`, `confirm` (dialog `window.confirm`), `noValidate`. Menampilkan `alert err/ok`. `SubmitButton` membaca status pending via context |
| `DeleteButton` | C | Tombol hapus dengan konfirmasi, memanggil action dengan `id` |
| `ExportButton` | C | Tautan `<a>` ke route ekspor; dinonaktifkan 4 detik setelah diklik agar tidak terklik ganda |
| `RefreshButton` | C | `router.refresh()` di dalam transition: memuat ulang data halaman tanpa Realtime |

### Peserta & foto

| Komponen | Fungsi |
|---|---|
| `ParticipantForm` [C] | Form peserta (nama, nama orang tua, gender, jenjang, kelas, usia, foto). Validasi klien dengan `validateParticipant` (fokus ke error pertama, `aria-invalid`). Membuat UUID di klien, **mengunggah foto langsung ke Storage** (`upsert: true`), lalu mengirim `photo_path` ke Server Action. Kolom Kelas disembunyikan untuk PAUD/TK/PGM |
| `PhotoField` [C] | Input file tanpa atribut `name` (berkas tidak ikut dikirim ke action). Memproses via `processPhoto`, menampilkan pratinjau dan ukuran, mereset diri saat event `reset` form, melindungi dari balapan (`seq` ref) |
| `EntryForm` [C] | Pendaftaran lomba: pilih slot, nama tim (bila team), pilih anggota (difilter menurut gender/jenjang slot), input tautan bila online |

### Penilaian

| Komponen | Fungsi |
|---|---|
| `ScoreForm` [C] | Input nilai per kriteria (dikelompokkan per `group_name`), pratinjau total real-time, konfirmasi sebelum kirim ("terkunci") |
| `ScoreCorrection` [C] | `ScoreEditButton` dan `ResetJudgeButton`, keduanya membuka dialog dengan alasan wajib. Hanya dirender untuk Super Admin |
| `LiveFeed` [C] | Daftar penilaian masuk, paginasi 10/halaman. Berlangganan Realtime `scores` (event `*`), *debounce* 500 ms karena satu penilaian = banyak baris. Menyorot item baru 2,5 detik; status koneksi: menghubungkan / live / terputus |

### Admin

| Komponen (`AdminForms.tsx`) [C] | Fungsi |
|---|---|
| `PublishButton` | Publikasi/batalkan publikasi hasil lomba |
| `StatusButton` | Diskualifikasi/pulihkan pendaftaran (alasan opsional) |
| `PenaltyForm` | Tambah/hapus penalti; `canEdit` menonaktifkan untuk non-Super |
| `ProfileForm` | Ubah nama, peran, desa akun |
| `JudgeAssignForm` | Centang lomba yang dinilai seorang juri |
| `CreateAccountDialog` | `<dialog>` buat akun. Sandi awal default **`kosong123`** |
| `AccountActions` | Reset sandi dan hapus akun |

`SettingsForms.tsx` [C]: `EventForm`, `SiteContentForm`.

### Navigasi & kerangka

| Komponen | Tipe | Fungsi |
|---|---|---|
| `Nav` | S | Header situs; tampilkan login atau `AccountMenu` |
| `AccountMenu` | C | Dropdown akun (tutup saat klik luar/Escape), dialog ganti sandi, tombol keluar |
| `PortalShell` | C | Layout portal: sidebar + konten. Sidebar bisa disembunyikan, preferensi di `localStorage["fgu-sidebar-collapsed"]` (dibungkus try/catch) |
| `SideNav` | C | Menu sisi dengan ikon SVG inline (`dashboard`, `users`, `trophy`, `key`, `settings`, `home`, `user`, `file`); penanda aktif lewat `usePathname`, opsi `exact` |

---

## 11. Library internal (`src/lib`)

| File | Isi |
|---|---|
| `types.ts` | `Role`, `Gender`, `Level`, dan interface `Profile`, `Village`, `Competition`, `Slot`, `Criterion`, `Participant`, `EventRow`, `ActionState` |
| `utils.ts` | Label (`GENDER_LABEL`, `LEVEL_LABEL`, `ROLE_LABEL`), `LEVELS`; format (`fmtScore`, `fmtDate`, `fmtDateTime`, zona WIB); `toLocalInput`/`fromLocalInput` (WIB ↔ ISO); `dbError`; `isEligible(peserta, lomba)`; `registrationStatus(event)`; `beforeDeadline` |
| `participant.ts` | Aturan validasi peserta yang **dipakai bersama klien dan server**: `validateParticipant`, `firstError`, `usesGrade`, `gradeRequired`, `binBinti` |
| `photo.ts` | Validasi + kompres foto di browser (hanya client) |
| `photoUrl.ts` | `signedPhotoUrls()` — signed URL (TTL 1 jam), batch via `createSignedUrls` |
| `auth.ts` | Helper sesi/peran (§5.3) |
| `excel.ts`, `exportGuard.ts` | Util ekspor (§8) |
| `compIcon.tsx` | `CompIcon`: ikon per lomba dicocokkan lewat regex nama lomba → PNG di `public/icons`, fallback emoji, lalu 🏆 |
| `supabase/*` | Tiga klien Supabase (§5.1) |

### Aturan validasi peserta (`participant.ts`)

| Field | Aturan |
|---|---|
| `full_name` | Wajib, ≤ 100 karakter |
| `parent_name` | Wajib, 2–100 karakter (dipakai untuk format "Nama **Bin/Binti** Orang tua" via `binBinti`) |
| `gender` | `L` atau `P` |
| `education_level` | Salah satu dari `PAUD, TK, SD, SMP, SMA, PGM` |
| `grade` | Wajib hanya untuk **SD**; bila diisi integer 1–12; tidak dipakai untuk PAUD/TK/PGM |
| `age` | Integer 1–100 |
| `photo` | Wajib (saat ubah, cukup bila peserta sudah punya foto) |

### Aturan foto (`photo.ts`)

- Format: JPG, PNG, WebP. **HEIC/HEIF ditolak** dengan pesan khusus. Berkas kosong ditolak.
- Batas: 5 MB (sama dengan bucket) dan sisi terpanjang 2560 px.
- Bila sudah memenuhi batas → diunggah **apa adanya**. Bila tidak → diperkecil dan dikodekan WebP
  dengan kualitas 0,9 → 0,8 → 0,7; bila browser tak mendukung WebP → JPEG berlatar putih.
- Orientasi EXIF diterapkan (`imageOrientation: "from-image"`, fallback `<img>.decode()`).
- Foto dipakai untuk video tron pengumuman juara, sehingga kualitas dijaga.

### `isEligible` (pre-filter, validasi akhir tetap di database)

Cek `levels`, `max_age`, `allowed_genders`, dan untuk jenjang SD yang lombanya punya rentang kelas:
`grade` wajib ada dan dalam `grade_min..grade_max`.

---

## 12. Fitur kunci secara mendalam

### 12.1 Pendaftaran peserta + foto

1. Admin Desa mengisi `ParticipantForm`; validasi klien berjalan.
2. Foto diproses di browser (`processPhoto`).
3. UUID peserta dibuat di klien; foto diunggah ke `participant-photos/{village}/{uuid}.{ext}`.
4. Server Action menerima `id` + `photo_path`, memvalidasi format path, lalu insert baris `participants`.

Alasan unggah langsung dari browser: batas 5 MB melebihi batas body Server Action.

*Risiko yang perlu diketahui:* bila upload berhasil tetapi insert gagal (mis. validasi DB), berkas
yatim tertinggal di bucket. Saat ubah foto, `upsert: true` menimpa berkas sebelum action memvalidasi.

### 12.2 Pendaftaran lomba

`EntryForm` → `createEntry`: cek `team_size` tepat, insert `entries`, insert `entry_members`.
Pelanggaran aturan (kuota, slot, satu peserta satu lomba, jadwal) ditolak trigger/RLS dan
diterjemahkan `dbError`. Pendaftaran hanya bisa dihapus saat jadwal pendaftaran dibuka.

### 12.3 Penilaian juri

1. Juri membuka `/juri/[slug]/[entryId]`; `ScoreForm` menampilkan kriteria dengan pratinjau total.
2. Konfirmasi → `submitScores` → RPC `submit_scores` (atomik, satu panggilan untuk semua kriteria).
3. Setelah tersimpan, halaman menampilkan mode **terkunci**. Hanya Super Admin yang dapat mengoreksi
   (dengan alasan, tercatat di `score_audit_log`) atau mereset input juri.
4. Setelah `results_published = true`, juri tidak bisa mengirim nilai baru untuk lomba itu.

### 12.4 Dashboard live

Server Component memuat data awal; `LiveFeed` lalu menyegarkan halaman aktif setiap ada perubahan
di tabel `scores` (debounce 500 ms). RLS tetap berlaku pada event Realtime dan query ulang.

### 12.5 Publikasi hasil

Admin menekan *Publikasikan hasil* → `competitions.results_published = true`. View
`public_winners` (beranda) dan `published_results` (Admin Desa) hanya menampilkan lomba terpublikasi.
Jumlah pemenang = `winner_count` teratas per kelompok peringkat.

### 12.6 Jadwal pendaftaran

`registrationStatus(event)` menurunkan buka/tutup dari `registration_opens_at`/`registration_closes_at`
(kosong = tak dibatasi di sisi itu). UI menyembunyikan form saat ditutup, tetapi **penegakan
sebenarnya di RLS database**. Tautan karya lomba online boleh diubah selama pendaftaran buka
**atau** sebelum `submission_deadline`.

---

## 13. Styling

- Satu berkas `globals.css` (~300 baris), tanpa CSS Modules/Tailwind.
- Token warna di `:root`: `--blue #1677c8`, `--blue2 #0b4f8a`, `--green`, `--red`, `--yellow`,
  `--ink`, `--muted`, `--bg`, `--card`, `--line`, `--shadow`.
- Kelas utilitas yang dipakai lintas halaman: `container`, `page`, `stack`, `row`, `between`, `grid g2/g3/g4`,
  `card`, `chip` (+ `green/yellow/red/gray`), `btn` (+ `primary/soft/ghost/white/green/danger`, `sm/lg`),
  `alert` (`ok/err/info`), `tablewrap`, `stat`, `bar`, `rank` (`r1/r2/r3`), `field`, `fields`.
- Layout portal: `.portal`, `.sidebar`, `.portal-main`, status `.collapsed`.
- Hanya tema terang; belum ada mode gelap.

---

## 14. Deploy

Target: **Vercel**. Impor repo dan isi env `NEXT_PUBLIC_SUPABASE_URL` serta
`NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY` (opsional `SUPABASE_IMAGE_TRANSFORM=1`). Jangan memasukkan
`service_role`/secret key. Pastikan migrasi SQL, bucket Storage, dan Realtime sudah aktif di project
Supabase produksi. `next.config.ts` masih kosong (tanpa `images.remotePatterns` karena foto peserta
ditampilkan dengan `<img>` biasa, bukan `next/image`).

---

## 15. Keamanan: yang sudah ada & catatan perbaikan

### Yang sudah baik

- RLS sebagai penjaga utama; tidak memakai `service_role`.
- Pemeriksaan peran berlapis (proxy → layout → action → RLS).
- Anti formula injection pada ekspor Excel, validasi UUID pada filter, rate limit ekspor.
- Validasi `photo_path` di server (harus berawalan `{village_id}/{id}.`) dan bucket private dengan signed URL.
- Signed URL berumur 1 jam; respons ekspor `no-store`.
- Alasan wajib dan audit log untuk koreksi nilai.

### Catatan / hal yang layak ditinjau

| # | Temuan | Saran |
|---|---|---|
| 1 | Sandi awal default `kosong123` di `AdminForms.tsx`, dan pesan sukses `createAccount`/`resetPassword` menampilkan sandi dalam teks biasa | Wajibkan ganti sandi saat login pertama, atau buat sandi acak sekali tampil |
| 2 | `fgu_01_schema.sql` & `fgu_02_seed.sql` tidak ada di repo | Tambahkan ke repo (atau folder `supabase/migrations`) agar setup reproducible |
| 3 | Batas atas nilai juri (`max_score`) di `submitScores` hanya dicek via atribut HTML | Pastikan `submit_scores` memvalidasi `score <= max_score` di database |
| 4 | Rate limit ekspor in-memory per instance | Di Vercel serverless, tiap instance punya peta sendiri; cukup untuk cegah klik beruntun, tidak untuk perlindungan sungguhan |
| 5 | Foto yatim bila insert peserta gagal setelah upload | Hapus berkas saat action gagal, atau bersihkan berkala |
| 6 | `createEntry` tidak transaksional (insert `entries` lalu `entry_members`, dengan kompensasi delete) | Pindahkan ke satu RPC |
| 7 | Jumlah desa "9" di-hardcode di dashboard (`admin/page.tsx`); teks juknis di beranda hardcoded | Ambil dari `villages.length` / pindahkan ke `site_content` |
| 8 | `.env.example` tidak mencantumkan `SUPABASE_IMAGE_TRANSFORM` | Tambahkan |
| 9 | README menyebut setup hanya sampai `fgu_03`; ada `fgu_04` | Perbarui README |
| 10 | Belum ada test otomatis | Prioritaskan test untuk `validateParticipant`, `isEligible`, `registrationStatus`, `sanitizeCell` (semuanya fungsi murni) |

---

## 16. Konvensi kode

- Bahasa UI dan komentar: **Bahasa Indonesia**; identifier dalam bahasa Inggris, dengan beberapa nama
  rute Indonesia (`desa`, `juri`, `lomba`, `peserta`, `hasil`, `akun`, `pengaturan`).
- Halaman = `async` Server Component; `"use client"` hanya untuk komponen interaktif.
- `params` dan `searchParams` selalu di-`await` (bertipe `Promise`).
- Server Action selalu mengembalikan `ActionState`, tidak melempar error ke UI; pesan DB dipetakan
  lewat `dbError`.
- Import memakai alias `@/` (mis. `@/lib/auth`).
- Hasil query disertakan tipe via `as` cast (belum ada tipe yang digenerate dari skema Supabase).
  Pertimbangkan `supabase gen types typescript`.
- Waktu selalu ditampilkan dalam zona **Asia/Jakarta**; input `datetime-local` dikonversi manual dengan
  offset +07:00.
