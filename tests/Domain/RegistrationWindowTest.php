<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\RegistrationWindow;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RegistrationWindowTest extends TestCase
{
    private const OPENS = '2026-10-01 00:00:00';
    private const CLOSES = '2026-10-10 00:00:00';

    public function testStatusTransitions(): void
    {
        $at = static fn (string $t) => new DateTimeImmutable($t);

        $this->assertSame(RegistrationWindow::NOT_OPEN, RegistrationWindow::status(self::OPENS, self::CLOSES, $at('2026-09-30 23:59:59')));
        $this->assertSame(RegistrationWindow::OPEN, RegistrationWindow::status(self::OPENS, self::CLOSES, $at('2026-10-01 00:00:00')));
        $this->assertSame(RegistrationWindow::OPEN, RegistrationWindow::status(self::OPENS, self::CLOSES, $at('2026-10-09 23:59:59')));
        $this->assertSame(RegistrationWindow::CLOSED, RegistrationWindow::status(self::OPENS, self::CLOSES, $at('2026-10-10 00:00:00')));
    }

    public function testNullBoundsAreUnrestricted(): void
    {
        $now = new DateTimeImmutable('2030-01-01');
        $this->assertTrue(RegistrationWindow::isOpen(null, null, $now));
        $this->assertTrue(RegistrationWindow::isOpen(null, '2031-01-01 00:00:00', $now));
        $this->assertTrue(RegistrationWindow::isOpen('2029-01-01 00:00:00', null, $now));
        $this->assertTrue(RegistrationWindow::isOpen('', '', $now));
        $this->assertFalse(RegistrationWindow::isOpen(null, '2029-01-01 00:00:00', $now));
    }
}
