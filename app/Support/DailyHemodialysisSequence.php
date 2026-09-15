<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class DailyHemodialysisSequence
{
    public const MONDAY_WEDNESDAY_FRIDAY = 'L-M-V';

    public const TUESDAY_THURSDAY_SATURDAY = 'M-J-S';

    public static function forDate(string|CarbonInterface $date): ?string
    {
        $dayOfWeek = $date instanceof CarbonInterface
            ? $date->dayOfWeekIso
            : Carbon::parse($date)->dayOfWeekIso;

        return match ($dayOfWeek) {
            CarbonInterface::MONDAY,
            CarbonInterface::WEDNESDAY,
            CarbonInterface::FRIDAY => self::MONDAY_WEDNESDAY_FRIDAY,
            CarbonInterface::TUESDAY,
            CarbonInterface::THURSDAY,
            CarbonInterface::SATURDAY => self::TUESDAY_THURSDAY_SATURDAY,
            default => null,
        };
    }
}
