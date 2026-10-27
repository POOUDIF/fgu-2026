<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * Format tampilan (Bahasa Indonesia, zona Asia/Jakarta). Waktu di database sudah WIB.
 */
final class Format
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public static function score(mixed $n): string
    {
        if ($n === null || $n === '') {
            return '-';
        }
        $f = round((float) $n, 2);
        $s = number_format($f, 2, ',', '.');

        return str_contains($s, ',') ? rtrim(rtrim($s, '0'), ',') : $s;
    }

    private const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public static function weekday(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::DAYS[(int) (new DateTimeImmutable($value))->format('w')];
    }

    public static function date(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $d = new DateTimeImmutable($value);

        return sprintf('%d %s %d', (int) $d->format('j'), self::MONTHS[(int) $d->format('n')], (int) $d->format('Y'));
    }

    public static function dateTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return self::date($value) . ', ' . (new DateTimeImmutable($value))->format('H:i') . ' WIB';
    }

    /** Untuk <input type="datetime-local">. */
    public static function toLocalInput(?string $value): string
    {
        return ($value === null || $value === '') ? '' : (new DateTimeImmutable($value))->format('Y-m-d\TH:i');
    }

    /** Dari <input type="datetime-local"> ke DATETIME; null bila kosong/tidak valid. */
    public static function fromLocalInput(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i|', $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s|', $value);

        return $d ? $d->format('Y-m-d H:i:s') : null;
    }

    public static function bytes(int $n): string
    {
        if ($n >= 1048576) {
            return number_format($n / 1048576, 1, ',', '.') . ' MB';
        }

        return number_format(max($n, 0) / 1024, 0, ',', '.') . ' KB';
    }
}
