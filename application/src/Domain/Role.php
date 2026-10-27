<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Peran pengguna dan aturan turunannya (padanan lib/auth.ts + matriks hak akses §5.4).
 */
final class Role
{
    public const VILLAGE_ADMIN = 'village_admin';
    public const JUDGE = 'judge';
    public const COMPETITION_ADMIN = 'competition_admin';
    public const REGIONAL_ADMIN = 'regional_admin';
    public const SUPER_ADMIN = 'super_admin';

    public const ALL = [self::VILLAGE_ADMIN, self::JUDGE, self::COMPETITION_ADMIN, self::REGIONAL_ADMIN, self::SUPER_ADMIN];

    public const LABELS = [
        self::VILLAGE_ADMIN => 'Admin Desa',
        self::JUDGE => 'Juri',
        self::COMPETITION_ADMIN => 'Admin Lomba',
        self::REGIONAL_ADMIN => 'Admin Daerah',
        self::SUPER_ADMIN => 'Super Admin',
    ];

    public static function label(?string $role): string
    {
        return self::LABELS[$role ?? ''] ?? '-';
    }

    public static function isValid(?string $role): bool
    {
        return in_array($role, self::ALL, true);
    }

    public static function isAdmin(?string $role): bool
    {
        return $role === self::SUPER_ADMIN || $role === self::REGIONAL_ADMIN;
    }

    public static function isSuper(?string $role): bool
    {
        return $role === self::SUPER_ADMIN;
    }

    /** Beranda (path) menurut peran. */
    public static function homeFor(?string $role): string
    {
        if (self::isAdmin($role)) {
            return 'admin';
        }

        if ($role === self::VILLAGE_ADMIN) {
            return 'desa';
        }
        if ($role === self::COMPETITION_ADMIN) {
            return 'admin-lomba';
        }

        return 'juri';
    }

    /**
     * Apakah $actorRole boleh mengelola akun dengan peran $targetRole?
     * Admin Daerah tidak boleh menyentuh akun admin (aturan updateProfile/resetPassword/deleteAccount).
     */
    public static function canManage(?string $actorRole, ?string $targetRole): bool
    {
        if (self::isSuper($actorRole)) {
            return true;
        }
        if ($actorRole === self::REGIONAL_ADMIN) {
            return !self::isAdmin($targetRole);
        }

        return false;
    }

    /** Peran yang boleh diberikan oleh $actorRole saat membuat/mengubah akun. */
    public static function assignableBy(?string $actorRole): array
    {
        if (self::isSuper($actorRole)) {
            return self::ALL;
        }
        if ($actorRole === self::REGIONAL_ADMIN) {
            return [self::VILLAGE_ADMIN, self::JUDGE, self::COMPETITION_ADMIN];
        }

        return [];
    }
}
