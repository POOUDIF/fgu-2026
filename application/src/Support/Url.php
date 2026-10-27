<?php

declare(strict_types=1);

namespace App\Support;

final class Url
{
    /**
     * @return string|null|false string = URL http(s) valid; null = kosong; false = tidak valid
     */
    public static function normalizeHttp(mixed $raw): string|null|false
    {
        $url = trim((string) $raw);
        if ($url === '') {
            return null;
        }
        if (mb_strlen($url) > 500 || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            return false;
        }

        return $url;
    }
}
