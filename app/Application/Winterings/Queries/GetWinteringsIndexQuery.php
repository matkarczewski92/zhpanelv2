<?php

namespace App\Application\Winterings\Queries;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use App\Models\Wintering;
use Illuminate\Support\Collection;

class GetWinteringsIndexQuery
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $cycleResolver
    ) {
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>}
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
            return ['rows' => []];
        }

        $animals = Animal::query()
            ->with([
                'animalType:id,name',
                'animalCategory:id,name',
            ])
            ->whereIn('id', $animalIds)
            ->get([
                'id',
                'name',
                'second_name',
                'sex',
                'animal_type_id',
                'animal_category_id',
            ])
            ->keyBy('id');

        $cyclesByAnimalId = $this->cycleResolver->resolveCurrentCyclesByAnimalIds($animalIds);

        $rows = collect($animalIds)
            ->map(function (int $animalId) use ($animals, $cyclesByAnimalId): ?array {
                /** @var Animal|null $animal */
                $animal = $animals->get($animalId);
                if (!$animal instanceof Animal) {
                    return null;
                }

                $cycleRows = $cyclesByAnimalId[$animalId] ?? collect();
                if (!$this->cycleResolver->isCycleActive($cycleRows)) {
                    return null;
                }

                return $this->buildRow($animal, $cycleRows);
            })
            ->filter(fn (?array $row): bool => $row !== null)
            ->values()
            ->sortBy(fn (array $row): string => mb_strtolower((string) ($row['name_plain'] ?? '')))
            ->values()
            ->all();

        return [
            'rows' => $rows,
        ];
    }

    /**
     * @param Collection<int, Wintering> $cycleRows
     * @return array<string, mixed>
     */
    private function buildRow(Animal $animal, Collection $cycleRows): array
    {
        $currentStageRow = $this->resolveCurrentStage($cycleRows);
        $nextStageRow = $this->resolveNextStage($cycleRows);

        $firstRow = $cycleRows->first();
        $lastRow = $cycleRows->last();

        $cycleStartReal = $firstRow?->start_date;
        $cycleStartPlanned = $firstRow?->planned_start_date;
        $cycleEndReal = $lastRow?->end_date;
        $cycleEndPlanned = $lastRow?->planned_end_date;

        $currentStageStartReal = $currentStageRow?->start_date;
        $currentStageStartPlanned = $currentStageRow?->planned_start_date;

        $nextStagePlannedStart = $nextStageRow?->planned_start_date;

        $namePlain = $this->sanitizePlainText($animal->name);

        return [
            'animal_id' => (int) $animal->id,
            'name_plain' => $namePlain !== '' ? $namePlain : ('#' . (int) $animal->id),
            'name_html' => $this->sanitizeNameHtml($animal->name),
            'second_name' => $this->sanitizePlainText($animal->second_name),
            'sex_label' => Sex::label((int) $animal->sex),
            'type_name' => (string) ($animal->animalType?->name ?? '-'),
            'category_name' => (string) ($animal->animalCategory?->name ?? '-'),
            'scheme' => (string) ($currentStageRow?->stage?->scheme ?? '-'),
            'season' => $firstRow?->season ? (int) $firstRow->season : null,
            'current_stage' => (string) ($currentStageRow?->stage?->title ?? '-'),
            'current_stage_start' => $currentStageStartReal?->toDateString() ?? $currentStageStartPlanned?->toDateString(),
            'current_stage_start_is_real' => $currentStageStartReal !== null,
            'next_stage' => $nextStageRow
                ? sprintf('%d. %s', (int) ($nextStageRow->stage?->order ?? 0), (string) ($nextStageRow->stage?->title ?? '-'))
                : null,
            'next_stage_hint' => $nextStagePlannedStart?->toDateString(),
            'cycle_start' => $cycleStartReal?->toDateString() ?? $cycleStartPlanned?->toDateString(),
            'cycle_end' => $cycleEndReal?->toDateString() ?? $cycleEndPlanned?->toDateString(),
            'cycle_start_is_real' => $cycleStartReal !== null,
            'cycle_end_is_real' => $cycleEndReal !== null,
            'profile_url' => route('panel.animals.show', $animal->id),
            'can_advance' => $nextStageRow instanceof Wintering,
            'advance_url' => $nextStageRow instanceof Wintering
                ? route('panel.winterings.advance-stage', [$animal->id, $nextStageRow->id])
                : null,
            'close_url' => route('panel.winterings.close', $animal->id),
        ];
    }

    /**
     * @param Collection<int, Wintering> $rows
     */
    private function resolveCurrentStage(Collection $rows): ?Wintering
    {
        if ($rows->isEmpty()) {
            return null;
        }

        $started = $rows->first(function (Wintering $row): bool {
            return $row->start_date !== null && $row->end_date === null;
        });
        if ($started instanceof Wintering) {
            return $started;
        }

        $planned = $rows->first(function (Wintering $row): bool {
            return $row->end_date === null;
        });
        if ($planned instanceof Wintering) {
            return $planned;
        }

        return $rows->last();
    }

    /**
     * @param Collection<int, Wintering> $rows
     */
    private function resolveNextStage(Collection $rows): ?Wintering
    {
        $next = $rows->first(function (Wintering $row): bool {
            return $row->start_date === null && $row->end_date === null;
        });

        return $next instanceof Wintering ? $next : null;
    }

    private function sanitizeNameHtml(?string $value): string
    {
        $clean = trim((string) $value);
        if ($clean === '') {
            return '-';
        }

        return strip_tags($clean, '<b><i><u>');
    }

    private function sanitizePlainText(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }
}
