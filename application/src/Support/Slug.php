<?php

declare(strict_types=1);

namespace App\Support;

/** Pembuat slug URL dari nama lomba. */
final class Slug
{
    public static function make(string $text, int $max = 80): string
    {
        $s = mb_strtolower(trim($text));
        $s = preg_replace('/&/u', ' dan ', $s) ?? $s;
        $s = preg_replace('/[^a-z0-9]+/u', '-', $s) ?? $s;
        $s = trim($s, '-');
        $s = rtrim(mb_substr($s, 0, $max), '-');

        return $s === '' ? 'lomba' : $s;
    }

    /** @param list<string> $taken slug yang sudah dipakai */
    public static function unique(string $base, array $taken): string
    {
        $candidate = $base;
        $n = 2;
        while (in_array($candidate, $taken, true)) {
            $candidate = $base . '-' . $n++;
        }

        return $candidate;
    }
}
