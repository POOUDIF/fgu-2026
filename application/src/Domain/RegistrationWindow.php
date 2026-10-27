<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Jadwal pendaftaran. Waktu disimpan sebagai DATETIME WIB; kosong = tidak dibatasi pada sisi itu
 * (juknis: "periode pendaftaran ditentukan panitia" → default terbuka).
 */
final class RegistrationWindow
{
    public const NOT_OPEN = 'not_open';
    public const OPEN = 'open';
    public const CLOSED = 'closed';

    public static function status(?string $opensAt, ?string $closesAt, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();

        if ($opensAt !== null && $opensAt !== '' && $now < new DateTimeImmutable($opensAt)) {
            return self::NOT_OPEN;
        }
        if ($closesAt !== null && $closesAt !== '' && $now >= new DateTimeImmutable($closesAt)) {
            return self::CLOSED;
        }

        return self::OPEN;
    }

    public static function isOpen(?string $opensAt, ?string $closesAt, ?DateTimeImmutable $now = null): bool
    {
        return self::status($opensAt, $closesAt, $now) === self::OPEN;
    }
}
