<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Hasil sebuah aksi bisnis (padanan ActionState): sukses/gagal + pesan untuk pengguna.
 * Service tidak melempar exception untuk kegagalan yang bisa dipulihkan pengguna.
 */
final class ActionResult
{
    /**
     * @param array<string,string> $errors  error per field (opsional)
     * @param array<string,mixed>  $data    data tambahan (mis. id baru)
     */
    private function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $errors = [],
        public readonly array $data = [],
    ) {
    }

    public static function ok(string $message = '', array $data = []): self
    {
        return new self(true, $message, [], $data);
    }

    public static function fail(string $message, array $errors = []): self
    {
        return new self(false, $message, $errors);
    }
}
