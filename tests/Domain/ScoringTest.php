<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Scoring;
use PHPUnit\Framework\TestCase;

final class ScoringTest extends TestCase
{
    public function testJudgeTotalIsSumOfCriteria(): void
    {
        $this->assertEqualsWithDelta(240.0, Scoring::judgeTotal([80, 60, 100]), 0.0001);
        $this->assertEqualsWithDelta(85.5, Scoring::judgeTotal(['70', '15.5']), 0.0001);
        $this->assertSame(0.0, Scoring::judgeTotal([]));
    }

    public function testJudgeTotalUsesCriterionWeights(): void
    {
        $score = Scoring::judgeTotal([
            ['score' => 80, 'max_score' => 100, 'weight' => 30],
            ['score' => 70, 'max_score' => 100, 'weight' => 70],
        ], 'weighted');

        $this->assertEqualsWithDelta(73.0, $score, 0.0001);
    }

    public function testPointsMethodSumsRawPoints(): void
    {
        $this->assertSame(140.0, Scoring::judgeTotal([
            ['score' => 160, 'max_score' => 100, 'weight' => 50],
            ['score' => -20, 'max_score' => 100, 'weight' => 50],
        ], 'points'));
    }

    public function testAccumulateAveragesJudgesAndSubtractsPenalty(): void
    {
        $r = Scoring::accumulate([80.0, 100.0], 5.0);
        $this->assertEqualsWithDelta(180.0, $r['sum_total'], 0.0001);
        $this->assertEqualsWithDelta(90.0, $r['avg_total'], 0.0001);
        $this->assertEqualsWithDelta(85.0, $r['final_score'], 0.0001);
        $this->assertSame(2, $r['judge_count']);
    }

    public function testAccumulateWithoutJudges(): void
    {
        $r = Scoring::accumulate([], 3.0);
        $this->assertNull($r['final_score']);
        $this->assertNull($r['sum_total']);
        $this->assertSame(0, $r['judge_count']);
    }

    public function testRankSharesTiesAndSkipsUnscored(): void
    {
        $out = Scoring::rank([
            ['id' => 1, 'final_score' => 90.0],
            ['id' => 2, 'final_score' => 80.0],
            ['id' => 3, 'final_score' => 80.0],
            ['id' => 4, 'final_score' => 70.0],
            ['id' => 5, 'final_score' => null],
        ]);

        $this->assertSame([1 => 1, 2 => 2, 3 => 2, 4 => 4, 5 => null], array_column($out, 'rank', 'id'));
    }

    public function testRankPreservesInputOrder(): void
    {
        $out = Scoring::rank([['id' => 'x', 'final_score' => 10.0], ['id' => 'y', 'final_score' => 20.0]]);
        $this->assertSame(['x', 'y'], array_column($out, 'id'));
        $this->assertSame([2, 1], array_column($out, 'rank'));
    }
}
