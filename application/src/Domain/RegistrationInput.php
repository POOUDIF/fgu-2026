<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\Url;

/**
 * Normalisasi & validasi isian pendaftaran peserta (nama, usia, tautan karya).
 * Murni: tidak menyentuh database/framework.
 */
final class RegistrationInput
{
    public const MAX_NAME = 100;

    /** Nama dinormalkan untuk aturan "satu orang satu lomba". */
    public static function nameKey(string $name): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        return mb_strtolower($collapsed);
    }

    public static function cleanName(string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);
    }

    /**
     * Gabungkan array paralel dari form menjadi baris; baris tanpa nama dibuang.
     *
     * @param array<int|string,mixed> $names
     * @param array<int|string,mixed> $ages
    * @param array<int|string,mixed> $links
    * @param array<int|string,mixed> $genders
    * @param array<int|string,mixed> $levels
    * @param array<int|string,mixed> $grades
    * @param array<int|string,mixed> $parents
     * @return list<array{name:string,age:string,link:string}>
     */
    public static function rows(array $names, array $ages, array $links, array $genders = [], array $levels = [], array $grades = [], array $parents = []): array
    {
        $rows = [];
        foreach ($names as $i => $name) {
            $clean = self::cleanName((string) $name);
            if ($clean === '') {
                continue;
            }
            $row = [
                'name' => $clean,
                'age' => trim((string) ($ages[$i] ?? '')),
                'link' => trim((string) ($links[$i] ?? '')),
            ];
            if ($genders !== [] || $levels !== [] || $grades !== [] || $parents !== []) {
                $row += [
                    'gender' => trim((string) ($genders[$i] ?? '')),
                    'education_level' => trim((string) ($levels[$i] ?? '')),
                    'grade' => trim((string) ($grades[$i] ?? '')),
                    'parent_name' => trim((string) ($parents[$i] ?? '')),
                ];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param list<array{name:string,age:string,link:string}> $rows
     * @return string|null pesan error pertama, atau null bila valid
     */
    public static function validate(array $rows, ?int $quota, bool $linksAllowed, ?int $minimumParticipants = null): ?string
    {
        if ($minimumParticipants !== null && count($rows) < $minimumParticipants) {
            return sprintf('Peserta minimal %d orang per desa.', $minimumParticipants);
        }
        if ($quota !== null && count($rows) > $quota) {
            return sprintf('Peserta melebihi kuota (maksimal %d orang per desa).', $quota);
        }

        $seen = [];
        foreach ($rows as $r) {
            if (mb_strlen($r['name']) > self::MAX_NAME) {
                return 'Nama peserta maksimal ' . self::MAX_NAME . ' karakter.';
            }
            if ($r['age'] !== '' && (!preg_match('/^\d{1,3}$/', $r['age']) || (int) $r['age'] < 1 || (int) $r['age'] > 100)) {
                return sprintf('Usia %s belum diisi dengan benar (1–100).', $r['name']);
            }
            $key = self::nameKey($r['name']);
            if (isset($seen[$key])) {
                return sprintf('Nama %s diisi lebih dari sekali.', $r['name']);
            }
            $seen[$key] = true;

            if ($linksAllowed && Url::normalizeHttp($r['link']) === false) {
                return sprintf('Tautan karya %s harus berupa URL http:// atau https:// yang valid.', $r['name']);
            }
        }

        return null;
    }
}
