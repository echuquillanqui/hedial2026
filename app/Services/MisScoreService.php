<?php

namespace App\Services;

class MisScoreService
{
    public function laboratoryScore(mixed $value, array $limits): ?int
    {
        if (! is_numeric($value)) return null;
        $value = (float) $value;
        foreach ($limits as $score => $minimum) if ($value >= $minimum) return $score;
        return 3;
    }

    public function albumin(mixed $value): ?int { return $this->laboratoryScore($value, [0 => 4.0, 1 => 3.5, 2 => 3.0]); }
    public function transferrin(mixed $value): ?int { return $this->laboratoryScore($value, [0 => 250, 1 => 200, 2 => 150]); }
    public function bmi(mixed $value): ?int { return $this->laboratoryScore($value, [0 => 20, 1 => 18, 2 => 16]); }

    public function total(array $scores): ?int
    {
        return collect($scores)->contains(fn ($score) => $score === null) ? null : array_sum($scores);
    }
}
