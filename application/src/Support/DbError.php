<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Memetakan error MySQL ke pesan yang ramah pengguna (padanan dbError di utils.ts).
 * Nama constraint pada schema sengaja distabilkan agar dapat dikenali di sini.
 */
final class DbError
{
    private const DUPLICATE = 1062;
    private const FK_CHILD = 1452;   // baris anak merujuk induk yang tidak ada
    private const FK_PARENT = 1451;  // induk masih dirujuk anak
    private const CHECK_FAILED = 3819;
    private const DEADLOCK = 1213;
    private const LOCK_TIMEOUT = 1205;

    /**
     * @param array{code?:int|string,message?:string}|null $error hasil $this->db->error()
     */
    public static function friendly(?array $error, string $fallback = 'Terjadi kesalahan saat menyimpan data.'): string
    {
        $code = (int) ($error['code'] ?? 0);
        $msg = (string) ($error['message'] ?? '');

        if ($code === self::DUPLICATE) {
            if (str_contains($msg, 'participants_one_per_village_name') || str_contains($msg, 'entry_members_one_entry')) {
                return 'Peserta sudah terdaftar di lomba lain (satu peserta hanya boleh mengikuti satu lomba).';
            }
            if (str_contains($msg, 'competitions_slug')) {
                return 'Nama lomba sudah dipakai.';
            }
            if (str_contains($msg, 'villages_name')) {
                return 'Nama desa sudah dipakai.';
            }
            if (str_contains($msg, 'users_email')) {
                return 'Email sudah dipakai akun lain.';
            }
            if (str_contains($msg, 'scores_one_per_criterion')) {
                return 'Nilai untuk pendaftaran ini sudah pernah dikirim.';
            }

            return 'Data sudah ada.';
        }
        if ($code === self::FK_PARENT) {
            return 'Data masih dipakai oleh data lain sehingga tidak dapat dihapus.';
        }
        if ($code === self::FK_CHILD) {
            return 'Data rujukan tidak ditemukan.';
        }
        if ($code === self::CHECK_FAILED) {
            return 'Nilai yang dimasukkan tidak memenuhi batasan data.';
        }
        if ($code === self::DEADLOCK || $code === self::LOCK_TIMEOUT) {
            return 'Server sedang sibuk, silakan coba lagi.';
        }

        return $fallback;
    }
}
