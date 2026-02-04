<?php

namespace App\Application\MassData\Support;

use Carbon\Carbon;

class MassDataFeedingDueCalculator
{
    public function calculate(?Carbon $lastFeedingAt, int $feedInterval): int
    {
        $nowDate = Carbon::now();

        $nextFeedDate = $lastFeedingAt
            ? $lastFeedingAt->copy()->addDays(max(0, $feedInterval))
            : null;

        $targetDate = $nextFeedDate ? Carbon::parse($nextFeedDate) : Carbon::parse('');
        $diff = $nowDate->copy()->diffInDays($targetDate, false);
        $diff = $diff < 0 ? $diff - 1 : $diff;

        return $nowDate->toDateString() === $targetDate->toDateString() ? $diff : $diff + 1;
    }
}

