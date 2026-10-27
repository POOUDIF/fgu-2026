<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pengaman ekspor Excel (padanan lib/excel.ts).
 */
final class CellSanitizer
{
    /** Anti formula injection: string berawalan = + - @ TAB CR diberi awalan apostrof. */
    public static function cell(mixed $value): mixed
    {
        // "-" tunggal dipakai sebagai penanda kosong dan bukan formula.
        if (!is_string($value) || $value === '' || $value === '-') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
    }

    /** Nama sheet: tanpa karakter terlarang, maks. 31 karakter, unik. */
    public static function sheetName(string $name, array $used = []): string
    {
        $clean = trim(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $name) ?? '');
        $clean = trim($clean, "'");
        $clean = $clean === '' ? 'Sheet' : $clean;
        $base = mb_substr($clean, 0, 31);

        $candidate = $base;
        $n = 2;
        $lower = array_map('mb_strtolower', $used);
        while (in_array(mb_strtolower($candidate), $lower, true)) {
            $suffix = ' (' . $n++ . ')';
            $candidate = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
        }

        return $candidate;
    }
}
