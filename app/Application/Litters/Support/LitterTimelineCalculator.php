<?php

namespace App\Application\Litters\Support;

use App\Models\Litter;
use App\Models\SystemConfig;
use Carbon\CarbonImmutable;

class LitterTimelineCalculator
{
    private ?int $layingDuration = null;
    private ?int $hatchingDuration = null;

    public function estimatedLayingDate(Litter $litter): ?CarbonImmutable
    {
        if ($litter->laying_date) {
            return CarbonImmutable::parse($litter->laying_date);
        }

        $referenceDate = $litter->connection_date ?: $litter->planned_connection_date;
        if (!$referenceDate) {
            return null;
        }

        return CarbonImmutable::parse($referenceDate)->addDays($this->getLayingDuration());
    }

    public function estimatedHatchingDate(Litter $litter): ?CarbonImmutable
    {
        if ($litter->hatching_date) {
            return CarbonImmutable::parse($litter->hatching_date);
        }

        $layingDate = $this->estimatedLayingDate($litter);
        if (!$layingDate) {
            return null;
        }

        return $layingDate->addDays($this->getHatchingDuration());
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   source:string,
     *   connection_date:string|null,
     *   laying_date:string|null,
     *   hatching_date:string|null,
     *   laying_duration_days:int,
     *   hatchling_duration_days:int
     * }
     */
    public function buildPlanning(Litter $litter, array $input): array
    {
        $source = $this->resolveSource($input);

        $connection = $this->parseNullableDate($input['planning_connection_date'] ?? null)
            ?? $this->parseNullableDate($litter->connection_date?->format('Y-m-d'))
            ?? $this->parseNullableDate($litter->planned_connection_date?->format('Y-m-d'));
        $laying = $this->parseNullableDate($input['planning_laying_date'] ?? null)
            ?? $this->parseNullableDate($litter->laying_date?->format('Y-m-d'));
        $hatching = $this->parseNullableDate($input['planning_hatching_date'] ?? null)
            ?? $this->parseNullableDate($litter->hatching_date?->format('Y-m-d'));

        $layingDuration = $this->getLayingDuration();
        $hatchingDuration = $this->getHatchingDuration();

        if ($source === 'connection' && $connection) {
            $laying = $connection->addDays($layingDuration);
            $hatching = $laying->addDays($hatchingDuration);
        } elseif ($source === 'laying' && $laying) {
            $connection = $laying->subDays($layingDuration);
            $hatching = $laying->addDays($hatchingDuration);
        } elseif ($source === 'hatching' && $hatching) {
            $laying = $hatching->subDays($hatchingDuration);
            $connection = $laying->subDays($layingDuration);
        } else {
            if (!$laying && $connection) {
                $laying = $connection->addDays($layingDuration);
            }
            if (!$hatching && $laying) {
                $hatching = $laying->addDays($hatchingDuration);
            }
        }

        return [
            'source' => $source,
            'connection_date' => $connection?->format('Y-m-d'),
            'laying_date' => $laying?->format('Y-m-d'),
            'hatching_date' => $hatching?->format('Y-m-d'),
            'laying_duration_days' => $layingDuration,
            'hatchling_duration_days' => $hatchingDuration,
        ];
    }

    public function getLayingDuration(): int
    {
        if ($this->layingDuration !== null) {
            return $this->layingDuration;
        }

        $value = (int) SystemConfig::query()->where('key', 'layingDuration')->value('value');
        $this->layingDuration = $value > 0 ? $value : 30;

        return $this->layingDuration;
    }

    public function getHatchingDuration(): int
    {
        if ($this->hatchingDuration !== null) {
            return $this->hatchingDuration;
        }

        $value = (int) SystemConfig::query()->where('key', 'hatchlingDuration')->value('value');
        $this->hatchingDuration = $value > 0 ? $value : 55;

        return $this->hatchingDuration;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveSource(array $input): string
    {
        $source = (string) ($input['planning_source'] ?? '');
        if (in_array($source, ['connection', 'laying', 'hatching'], true)) {
            return $source;
        }

        if (!empty($input['planning_connection_date'])) {
            return 'connection';
        }

        if (!empty($input['planning_laying_date'])) {
            return 'laying';
        }

        if (!empty($input['planning_hatching_date'])) {
            return 'hatching';
        }

        return 'connection';
    }

    private function parseNullableDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return CarbonImmutable::parse($normalized);
    }
}
