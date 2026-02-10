<?php

namespace App\Application\Winterings\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class WinteringTimelineCalculator
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public function recalculateForward(array $rows, int $anchorIndex = 0): array
    {
        if ($rows === []) {
            return $rows;
        }

        $anchorIndex = max(0, min($anchorIndex, count($rows) - 1));
        $anchorStart = $this->resolveAnchorStart($rows, $anchorIndex);
        $currentStart = $anchorStart;

        for ($i = $anchorIndex; $i < count($rows); $i++) {
            if ($i > $anchorIndex) {
                $previous = $rows[$i - 1];
                $previousEnd = $this->parseDate($previous['end_date'] ?? null)
                    ?? $this->parseDate($previous['planned_end_date'] ?? null);
                if ($previousEnd instanceof CarbonInterface) {
                    $currentStart = Carbon::instance($previousEnd);
                }
            }

            $duration = $this->resolveDuration($rows[$i]);
            $currentEnd = $currentStart->copy()->addDays($duration);

            $rows[$i]['planned_start_date'] = $currentStart->toDateString();
            $rows[$i]['planned_end_date'] = $currentEnd->toDateString();
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveAnchorStart(array $rows, int $anchorIndex): Carbon
    {
        $anchor = $rows[$anchorIndex];
        $duration = $this->resolveDuration($anchor);

        $plannedStart = $this->parseDate($anchor['planned_start_date'] ?? null);
        if ($plannedStart instanceof CarbonInterface) {
            return Carbon::instance($plannedStart);
        }

        $plannedEnd = $this->parseDate($anchor['planned_end_date'] ?? null);
        if ($plannedEnd instanceof CarbonInterface) {
            return Carbon::instance($plannedEnd)->subDays($duration);
        }

        if ($anchorIndex > 0) {
            $previous = $rows[$anchorIndex - 1];
            $previousEnd = $this->parseDate($previous['end_date'] ?? null)
                ?? $this->parseDate($previous['planned_end_date'] ?? null);
            if ($previousEnd instanceof CarbonInterface) {
                return Carbon::instance($previousEnd);
            }
        }

        $startDate = $this->parseDate($anchor['start_date'] ?? null);
        if ($startDate instanceof CarbonInterface) {
            return Carbon::instance($startDate);
        }

        return Carbon::today();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveDuration(array $row): int
    {
        $customDuration = $row['custom_duration'] ?? null;
        if (is_numeric($customDuration)) {
            return max(0, (int) $customDuration);
        }

        return max(0, (int) ($row['default_duration'] ?? 0));
    }

    private function parseDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

