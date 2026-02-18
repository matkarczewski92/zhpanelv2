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
     * Recalculate planned dates forward and backward around selected anchor row.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public function recalculateAroundAnchor(array $rows, int $anchorIndex = 0, string $anchorField = 'planned-start'): array
    {
        if ($rows === []) {
            return $rows;
        }

        $anchorIndex = max(0, min($anchorIndex, count($rows) - 1));

        if (in_array($anchorField, ['start', 'planned-start'], true)) {
            $value = trim((string) ($rows[$anchorIndex]['start_date'] ?? ''));
            if ($value === '') {
                $value = trim((string) ($rows[$anchorIndex]['planned_start_date'] ?? ''));
            }

            if ($value !== '') {
                $rows[$anchorIndex]['planned_start_date'] = $value;
            }
        }

        if (in_array($anchorField, ['end', 'planned-end'], true)) {
            $value = trim((string) ($rows[$anchorIndex]['end_date'] ?? ''));
            if ($value === '') {
                $value = trim((string) ($rows[$anchorIndex]['planned_end_date'] ?? ''));
            }

            if ($value !== '') {
                $rows[$anchorIndex]['planned_end_date'] = $value;
            }
        }

        $anchorStart = $this->resolveAnchorStartForAroundMode($rows, $anchorIndex, $anchorField);
        $anchorDuration = $this->resolveDuration($rows[$anchorIndex]);

        $rows[$anchorIndex]['planned_start_date'] = $anchorStart->toDateString();
        $rows[$anchorIndex]['planned_end_date'] = $anchorStart->copy()->addDays($anchorDuration)->toDateString();

        for ($i = $anchorIndex + 1; $i < count($rows); $i++) {
            $previousEnd = $this->parseDate($rows[$i - 1]['planned_end_date'] ?? null);
            if (!$previousEnd instanceof CarbonInterface) {
                continue;
            }

            $duration = $this->resolveDuration($rows[$i]);
            $rows[$i]['planned_start_date'] = $previousEnd->toDateString();
            $rows[$i]['planned_end_date'] = $previousEnd->copy()->addDays($duration)->toDateString();
        }

        for ($i = $anchorIndex - 1; $i >= 0; $i--) {
            $nextStart = $this->parseDate($rows[$i + 1]['planned_start_date'] ?? null);
            if (!$nextStart instanceof CarbonInterface) {
                continue;
            }

            $duration = $this->resolveDuration($rows[$i]);
            $start = $nextStart->copy()->subDays($duration);
            $rows[$i]['planned_start_date'] = $start->toDateString();
            $rows[$i]['planned_end_date'] = $nextStart->toDateString();
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
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveAnchorStartForAroundMode(array $rows, int $anchorIndex, string $anchorField): Carbon
    {
        $anchor = $rows[$anchorIndex];
        $duration = $this->resolveDuration($anchor);

        $readStart = $this->parseDate($anchor['planned_start_date'] ?? null)
            ?? $this->parseDate($anchor['start_date'] ?? null);
        $readEnd = $this->parseDate($anchor['planned_end_date'] ?? null)
            ?? $this->parseDate($anchor['end_date'] ?? null);

        if (in_array($anchorField, ['planned-end', 'end'], true) && $readEnd instanceof CarbonInterface) {
            return Carbon::instance($readEnd)->subDays($duration);
        }

        if ($readStart instanceof CarbonInterface) {
            return Carbon::instance($readStart);
        }

        if ($readEnd instanceof CarbonInterface) {
            return Carbon::instance($readEnd)->subDays($duration);
        }

        if ($anchorIndex > 0) {
            $previousEnd = $this->parseDate($rows[$anchorIndex - 1]['planned_end_date'] ?? null);
            if ($previousEnd instanceof CarbonInterface) {
                return Carbon::instance($previousEnd);
            }
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
