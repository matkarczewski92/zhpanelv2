<?php

namespace App\Application\Winterings\Commands;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Application\Winterings\Support\WinteringTimelineCalculator;
use App\Models\Wintering;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecalculateAllWinteringDatesCommand
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $cycleResolver,
        private readonly WinteringTimelineCalculator $timelineCalculator
    ) {
    }

    /**
     * @return array{cycles:int, rows:int}
     */
    public function handle(): array
    {
        $animalIds = Wintering::query()
            ->whereNotNull('animal_id')
            ->distinct()
            ->pluck('animal_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($animalIds === []) {
            return ['cycles' => 0, 'rows' => 0];
        }

        $cyclesByAnimal = $this->cycleResolver->resolveCurrentCyclesByAnimalIds($animalIds);

        $updatedCycles = 0;
        $updatedRows = 0;

        DB::transaction(function () use ($cyclesByAnimal, &$updatedCycles, &$updatedRows): void {
            foreach ($cyclesByAnimal as $cycleRows) {
                if (!$cycleRows instanceof Collection || $cycleRows->isEmpty()) {
                    continue;
                }

                if (!$this->cycleResolver->isCycleActive($cycleRows)) {
                    continue;
                }

                [$cycleChanged, $rowsChanged] = $this->recalculateCycle($cycleRows->values());
                if ($cycleChanged) {
                    $updatedCycles++;
                    $updatedRows += $rowsChanged;
                }
            }
        });

        return [
            'cycles' => $updatedCycles,
            'rows' => $updatedRows,
        ];
    }

    /**
     * @param Collection<int, Wintering> $cycleRows
     * @return array{0:bool,1:int}
     */
    private function recalculateCycle(Collection $cycleRows): array
    {
        $rows = $cycleRows
            ->map(fn (Wintering $row): array => [
                'default_duration' => (int) ($row->stage?->duration ?? 0),
                'custom_duration' => $row->custom_duration !== null ? (int) $row->custom_duration : null,
                'planned_start_date' => $row->planned_start_date?->toDateString(),
                'planned_end_date' => $row->planned_end_date?->toDateString(),
                'start_date' => $row->start_date?->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
            ])
            ->values()
            ->all();

        $anchorIndex = $this->resolveAnchorIndex($rows);
        $rows = $this->timelineCalculator->recalculateAroundAnchor($rows, $anchorIndex, 'planned-start');

        $changed = 0;
        foreach ($rows as $index => $row) {
            /** @var Wintering|null $model */
            $model = $cycleRows->get($index);
            if (!$model instanceof Wintering) {
                continue;
            }

            $newStart = $this->normalizeDate($row['planned_start_date'] ?? null);
            $newEnd = $this->normalizeDate($row['planned_end_date'] ?? null);
            $oldStart = $model->planned_start_date?->toDateString();
            $oldEnd = $model->planned_end_date?->toDateString();

            if ($newStart === $oldStart && $newEnd === $oldEnd) {
                continue;
            }

            $model->planned_start_date = $newStart;
            $model->planned_end_date = $newEnd;
            $model->save();
            $changed++;
        }

        return [$changed > 0, $changed];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveAnchorIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $realStart = trim((string) ($row['start_date'] ?? ''));
            $realEnd = trim((string) ($row['end_date'] ?? ''));
            $plannedStart = trim((string) ($row['planned_start_date'] ?? ''));
            $plannedEnd = trim((string) ($row['planned_end_date'] ?? ''));

            if ($realStart !== '' || $realEnd !== '' || $plannedStart !== '' || $plannedEnd !== '') {
                return (int) $index;
            }
        }

        return 0;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }
}

