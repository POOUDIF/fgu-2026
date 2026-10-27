<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\CellSanitizer;
use App\Support\DbError;
use App\Support\Format;
use App\Support\Password;
use App\Support\Slug;
use App\Support\Url;
use PHPUnit\Framework\TestCase;

final class SupportTest extends TestCase
{
    public function testCellSanitizerNeutralisesFormulas(): void
    {
        foreach (['=1+1', '+1', '@SUM(A1)', "\tx", "\rx", '-1+1'] as $v) {
            $this->assertSame("'" . $v, CellSanitizer::cell($v));
        }
        $this->assertSame('aman', CellSanitizer::cell('aman'));
        $this->assertSame('-', CellSanitizer::cell('-'));
        $this->assertSame(5, CellSanitizer::cell(5));
        $this->assertSame('', CellSanitizer::cell(''));
        $this->assertNull(CellSanitizer::cell(null));
    }

    public function testSheetName(): void
    {
        $this->assertSame('a b c', CellSanitizer::sheetName('a/b:c'));
        $this->assertSame('Sheet', CellSanitizer::sheetName('   '));
        $this->assertSame(31, mb_strlen(CellSanitizer::sheetName(str_repeat('x', 50))));
        $this->assertSame('Hasil (2)', CellSanitizer::sheetName('Hasil', ['hasil']));
        $this->assertSame('Hasil (3)', CellSanitizer::sheetName('Hasil', ['Hasil', 'Hasil (2)']));
    }

    public function testSlug(): void
    {
        $this->assertSame('lomba-mewarnai-dan-menceritakan', Slug::make('Lomba Mewarnai & Menceritakan'));
        $this->assertSame('nasehat-pidato-agama-da-i-cilik', Slug::make("Nasehat / Pidato Agama (Da'i Cilik)"));
        $this->assertSame('lomba', Slug::make('!!!'));
        $this->assertSame('cerdas-cermat-2', Slug::unique('cerdas-cermat', ['cerdas-cermat']));
        $this->assertSame('cerdas-cermat-3', Slug::unique('cerdas-cermat', ['cerdas-cermat', 'cerdas-cermat-2']));
        $this->assertSame('baru', Slug::unique('baru', ['lama']));
    }

    public function testUrlNormalize(): void
    {
        $this->assertSame('https://drive.google.com/x', Url::normalizeHttp(' https://drive.google.com/x '));
        $this->assertNull(Url::normalizeHttp('   '));
        $this->assertFalse(Url::normalizeHttp('javascript:alert(1)'));
        $this->assertFalse(Url::normalizeHttp('ftp://a.id/f'));
        $this->assertFalse(Url::normalizeHttp('bukan url'));
        $this->assertFalse(Url::normalizeHttp('https://a.id/' . str_repeat('x', 500)));
    }

    public function testFormatScore(): void
    {
        $this->assertSame('85', Format::score(85));
        $this->assertSame('85,5', Format::score('85.50'));
        $this->assertSame('85,46', Format::score(85.456));
        $this->assertSame('1.000', Format::score(1000));
        $this->assertSame('-', Format::score(null));
    }

    public function testFormatDates(): void
    {
        $this->assertSame('6 Desember 2026', Format::date('2026-12-06'));
        $this->assertSame('Minggu', Format::weekday('2026-11-01'));
        $this->assertSame('', Format::weekday(null));
        $this->assertSame('9 Oktober 2026, 08:05 WIB', Format::dateTime('2026-10-09 08:05:00'));
        $this->assertSame('-', Format::date(null));
        $this->assertSame('2026-10-09T08:05', Format::toLocalInput('2026-10-09 08:05:00'));
        $this->assertSame('2026-10-09 08:05:00', Format::fromLocalInput('2026-10-09T08:05'));
        $this->assertNull(Format::fromLocalInput(''));
        $this->assertNull(Format::fromLocalInput('bukan-tanggal'));
    }

    public function testDbErrorMapping(): void
    {
        $dup = static fn (string $key) => ['code' => 1062, 'message' => "Duplicate entry '1' for key '$key'"];

        $this->assertStringContainsString('satu peserta', DbError::friendly($dup('participants.participants_one_per_village_name')));
        $this->assertStringContainsString('Email', DbError::friendly($dup('users.users_email')));
        $this->assertStringContainsString('desa', DbError::friendly($dup('villages.villages_name')));
        $this->assertSame('Data sudah ada.', DbError::friendly($dup('apa_saja')));
        $this->assertStringContainsString('masih dipakai', DbError::friendly(['code' => 1451, 'message' => '']));
        $this->assertSame('fallback', DbError::friendly(['code' => 9999, 'message' => 'x'], 'fallback'));
        $this->assertSame('fallback', DbError::friendly(null, 'fallback'));
    }

    public function testPassword(): void
    {
        $p = Password::generate();
        $this->assertSame(12, strlen($p));
        $this->assertNotSame($p, Password::generate());
        $this->assertNotNull(Password::validate('pendek'));
        $this->assertNull(Password::validate('panjangnya8'));
        $this->assertNotNull(Password::validate(str_repeat('a', 73)));
        $this->assertTrue(password_verify('rahasia123', Password::hash('rahasia123')));
    }
}
