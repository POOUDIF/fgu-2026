<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\CerdasCermat;
use PHPUnit\Framework\TestCase;

final class CerdasCermatTest extends TestCase
{
    public function testFourRoundPointActions(): void
    {
        $this->assertSame(100.0, CerdasCermat::point_delta(1, 'add'));
        $this->assertSame(100.0, CerdasCermat::point_delta(2, 'add'));
        $this->assertSame(50.0, CerdasCermat::point_delta(3, 'add'));
        $this->assertSame(-20.0, CerdasCermat::point_delta(3, 'subtract'));
        $this->assertSame(100.0, CerdasCermat::point_delta(4, 'add'));
        $this->assertSame(-50.0, CerdasCermat::point_delta(4, 'subtract'));
    }

    public function testOnlyConfiguredAnswerActionsAreAllowed(): void
    {
        $this->assertNull(CerdasCermat::point_delta(1, 'subtract'));
        $this->assertNull(CerdasCermat::point_delta(2, 'subtract'));
        $this->assertNull(CerdasCermat::point_delta(5, 'add'));
        $this->assertNull(CerdasCermat::point_delta(3, 'unknown'));
    }

    public function testDisciplinePenaltyUsesRoundSpecificKey(): void
    {
        $this->assertSame('babak_1', CerdasCermat::penalty_key(1));
        $this->assertSame('babak_4', CerdasCermat::penalty_key(4));
    }

    public function testRoundNavigationStopsAtTheFirstAndLastRound(): void
    {
        $this->assertNull(CerdasCermat::previous_round(1));
        $this->assertSame(2, CerdasCermat::next_round(1));
        $this->assertSame(4, CerdasCermat::next_round(3));
        $this->assertNull(CerdasCermat::next_round(4));
        $this->assertSame(3, CerdasCermat::previous_round(4));
    }
}
