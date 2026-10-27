<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\RegistrationInput;
use PHPUnit\Framework\TestCase;

final class RegistrationInputTest extends TestCase
{
    public function testRowsDropBlankNamesAndCollapseSpaces(): void
    {
        $rows = RegistrationInput::rows(['  Ahmad   Fauzi ', '', 'Siti'], ['10', '9', '8'], ['', 'x', 'https://a.id']);

        $this->assertCount(2, $rows);
        $this->assertSame(['name' => 'Ahmad Fauzi', 'age' => '10', 'link' => ''], $rows[0]);
        $this->assertSame(['name' => 'Siti', 'age' => '8', 'link' => 'https://a.id'], $rows[1]);
    }

    public function testNameKeyIsCaseAndSpaceInsensitive(): void
    {
        $this->assertSame('budi santoso', RegistrationInput::nameKey("  BUDI   Santoso "));
        $this->assertSame(RegistrationInput::nameKey('Siti'), RegistrationInput::nameKey('siti '));
    }

    public function testValidRows(): void
    {
        $rows = [['name' => 'A', 'age' => '7', 'link' => ''], ['name' => 'B', 'age' => '100', 'link' => 'https://x.id/a']];
        $this->assertNull(RegistrationInput::validate($rows, 2, true));
        $this->assertNull(RegistrationInput::validate($rows, null, true));
        $this->assertNull(RegistrationInput::validate([], 1, false));
    }

    public function testQuotaExceeded(): void
    {
        $rows = [['name' => 'A', 'age' => '7', 'link' => ''], ['name' => 'B', 'age' => '8', 'link' => '']];
        $this->assertStringContainsString('kuota', (string) RegistrationInput::validate($rows, 1, false));
    }

    public function testMinimumParticipantCountAllowsNineOfTen(): void
    {
        $rows = array_map(
            static fn (int $number): array => ['name' => 'Peserta '.$number, 'age' => '', 'link' => ''],
            range(1, 9)
        );

        $this->assertNull(RegistrationInput::validate($rows, 10, false, 5));
    }

    public function testMinimumParticipantCountIsEnforced(): void
    {
        $rows = array_map(
            static fn (int $number): array => ['name' => 'Peserta '.$number, 'age' => '', 'link' => ''],
            range(1, 4)
        );

        $this->assertStringContainsString('minimal 5', (string) RegistrationInput::validate($rows, 10, false, 5));
    }

    public function testAgeIsOptionalAndValidatedWhenProvided(): void
    {
        $this->assertNull(RegistrationInput::validate([['name' => 'A', 'age' => '', 'link' => '']], null, false));
        foreach (['0', '101', 'abc', '5.5', '-3'] as $age) {
            $this->assertNotNull(RegistrationInput::validate([['name' => 'A', 'age' => $age, 'link' => '']], null, false), "usia '$age'");
        }
    }

    public function testDuplicateNamesRejected(): void
    {
        $rows = [['name' => 'Ani', 'age' => '7', 'link' => ''], ['name' => 'ani', 'age' => '8', 'link' => '']];
        $this->assertStringContainsString('lebih dari sekali', (string) RegistrationInput::validate($rows, null, false));
    }

    public function testLinksOnlyValidatedWhenAllowed(): void
    {
        $bad = [['name' => 'A', 'age' => '7', 'link' => 'javascript:alert(1)']];
        $this->assertNotNull(RegistrationInput::validate($bad, null, true));
        $this->assertNull(RegistrationInput::validate($bad, null, false));
    }

    public function testNameTooLong(): void
    {
        $this->assertNotNull(RegistrationInput::validate([['name' => str_repeat('a', 101), 'age' => '7', 'link' => '']], null, false));
    }
}
