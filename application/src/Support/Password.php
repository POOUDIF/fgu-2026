<?php

declare(strict_types=1);

namespace App\Support;

final class Password
{
    public const MIN_LENGTH = 8;

    /** Tanpa karakter yang mudah tertukar (0/O, 1/l/I). */
    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(int $length = 12): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /** @return string|null pesan error, atau null bila valid */
    public static function validate(string $plain): ?string
    {
        if (mb_strlen($plain) < self::MIN_LENGTH) {
            return 'Kata sandi minimal ' . self::MIN_LENGTH . ' karakter.';
        }
        if (mb_strlen($plain) > 72) {
            return 'Kata sandi maksimal 72 karakter.'; // batas bcrypt
        }

        return null;
    }
}
