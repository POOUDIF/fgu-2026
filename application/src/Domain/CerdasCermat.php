<?php

declare(strict_types=1);

namespace App\Domain;

final class CerdasCermat
{
    /** @return array<int,array{label:string,add:int,subtract:?int}> */
    public static function rounds(): array
    {
        return array(
            1 => array('label' => 'Babak 1', 'add' => 100, 'subtract' => NULL),
            2 => array('label' => 'Babak 2', 'add' => 100, 'subtract' => NULL),
            3 => array('label' => 'Babak 3', 'add' => 50, 'subtract' => 20),
            4 => array('label' => 'Babak 4', 'add' => 100, 'subtract' => 50),
        );
    }

    public static function point_delta(int $round, string $change): ?float
    {
        $spec = self::rounds()[$round] ?? NULL;
        if ($spec === NULL)
        {
            return NULL;
        }
        if ($change === 'add')
        {
            return (float) $spec['add'];
        }
        if ($change === 'subtract' && $spec['subtract'] !== NULL)
        {
            return (float) -$spec['subtract'];
        }

        return NULL;
    }

    public static function penalty_key(int $round): string
    {
        return 'babak_'.$round;
    }

    public static function next_round(int $round): ?int
    {
        return $round >= 1 && $round < 4 ? $round + 1 : NULL;
    }

    public static function previous_round(int $round): ?int
    {
        return $round > 1 && $round <= 4 ? $round - 1 : NULL;
    }
}
