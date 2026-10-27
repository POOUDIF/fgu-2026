<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Rumus nilai FGU 3.0:
 *  - total juri berbobot = 100 × Σ(nilai / nilai maks × bobot) / Σ(bobot)
 *  - total poin          = Σ poin kriteria
 *  - nilai akhir         = rata-rata total juri − penalti
 *  - peringkat        = nilai akhir tertinggi; nilai sama berbagi peringkat
 */
final class Scoring
{
    /** @param list<float|int|string|array{score:float,max_score:float,weight:float}> $scores */
    public static function judgeTotal(array $scores, string $method = 'sum'): float
    {
        if ($method === 'points' || $method === 'sum') {
            return round(array_sum(array_map(static fn ($s) => (float) (is_array($s) ? $s['score'] : $s), $scores)), 4);
        }

        $weighted = 0.0;
        $totalWeight = 0.0;
        foreach ($scores as $item) {
            if (!is_array($item)) {
                continue;
            }
            $max = (float) ($item['max_score'] ?? 0);
            $weight = (float) ($item['weight'] ?? 0);
            if ($max <= 0 || $weight <= 0) {
                continue;
            }
            $weighted += (float) $item['score'] / $max * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round(100 * $weighted / $totalWeight, 4) : 0.0;
    }

    /**
     * @param list<float> $judgeTotals total tiap juri yang sudah menilai
     * @return array{sum_total:?float,avg_total:?float,penalty:float,final_score:?float,judge_count:int}
     */
    public static function accumulate(array $judgeTotals, float $penalty): array
    {
        $n = count($judgeTotals);
        if ($n === 0) {
            return ['sum_total' => null, 'avg_total' => null, 'penalty' => $penalty, 'final_score' => null, 'judge_count' => 0];
        }
        $sum = array_sum($judgeTotals);

        return [
            'sum_total' => round($sum, 4),
            'avg_total' => round($sum / $n, 4),
            'penalty' => $penalty,
            'final_score' => round($sum / $n - $penalty, 4),
            'judge_count' => $n,
        ];
    }

    /**
     * Peringkat kompetisi standar (seri berbagi peringkat; berikutnya melompat).
     * Elemen tanpa final_score tidak diperingkat (rank = null). Urutan input dipertahankan.
     *
     * @param list<array<string,mixed>> $results harus memuat 'final_score'
     * @return list<array<string,mixed>> ditambah kunci 'rank'
     */
    public static function rank(array $results): array
    {
        $idxs = [];
        foreach ($results as $i => $r) {
            $results[$i]['rank'] = null;
            if ($r['final_score'] !== null) {
                $idxs[] = $i;
            }
        }

        usort($idxs, static fn (int $a, int $b) => $results[$b]['final_score'] <=> $results[$a]['final_score']);
        $rank = 0;
        $prev = null;
        foreach ($idxs as $pos => $i) {
            $score = round((float) $results[$i]['final_score'], 4);
            if ($prev === null || $score !== $prev) {
                $rank = $pos + 1;
                $prev = $score;
            }
            $results[$i]['rank'] = $rank;
        }

        return $results;
    }
}
