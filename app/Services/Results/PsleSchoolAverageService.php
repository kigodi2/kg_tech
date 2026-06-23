<?php

namespace App\Services\Results;

class PsleSchoolAverageService
{
    /**
     * Calculate school-level average marks (WASTANI wa Alama) and stats.
     * Use satCount as the canonical denominator.
     */
    public static function calculate(float $totalMarksSum, int $satCount, int $registeredCount): array
    {
        $average = $satCount > 0 ? round($totalMarksSum / $satCount, 4) : 0.0;

        return [
            'average' => $average,
            'denominator' => $satCount,
            'sat_count' => $satCount,
            'registered_count' => $registeredCount,
        ];
    }
}
