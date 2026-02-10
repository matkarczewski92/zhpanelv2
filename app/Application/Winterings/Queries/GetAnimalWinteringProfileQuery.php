<?php

namespace App\Application\Winterings\Queries;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Models\Animal;
use App\Models\Wintering;
use App\Models\WinteringStage;
use Illuminate\Support\Collection;

class GetAnimalWinteringProfileQuery
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $cycleResolver
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(Animal $animal): array
    {
        $cycleRows = $this->cycleResolver->resolveCurrentCycleForAnimal($animal->id);
        $hasCycle = $cycleRows->isNotEmpty();
        $isActive = $this->cycleResolver->isCycleActive($cycleRows);

        $schemes = WinteringStage::query()
            ->orderBy('scheme')
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'scheme', 'order', 'title', 'duration'])
            ->groupBy(fn (WinteringStage $stage): string => trim((string) $stage->scheme))
            ->map(function (Collection $stages): array {
                return $stages
                    ->sortBy('order')
                    ->values()
                    ->map(fn (WinteringStage $stage): array => [
                        'id' => (int) $stage->id,
                        'order' => (int) $stage->order,
                        'title' => (string) $stage->title,
                        'duration' => (int) $stage->duration,
                    ])
                    ->all();
            })
            ->all();

        $schemeNames = array_keys($schemes);
        $selectedScheme = $hasCycle
            ? (string) ($cycleRows->first()?->stage?->scheme ?? '')
            : ((string) ($schemeNames[0] ?? ''));

        $editorRows = $hasCycle
            ? $this->buildEditorRowsFromCycle($animal, $cycleRows)
            : [];

        $currentStage = $this->resolveCurrentStage($cycleRows);
        $firstRow = $cycleRows->first();
        $lastRow = $cycleRows->last();

        $startReal = $firstRow?->start_date;
        $startPlanned = $firstRow?->planned_start_date;
        $endReal = $lastRow?->end_date;
        $endPlanned = $lastRow?->planned_end_date;

        return [
            'active' => $isActive,
            'exists' => $hasCycle,
            'is_wintering' => $isActive,
            'scheme' => $selectedScheme !== '' ? $selectedScheme : null,
            'stage' => $currentStage?->stage?->title,
            'season' => $firstRow?->season,
            'start' => $startReal?->toDateString() ?? $startPlanned?->toDateString(),
            'end' => $endReal?->toDateString() ?? $endPlanned?->toDateString(),
            'start_is_real' => $startReal !== null,
            'end_is_real' => $endReal !== null,
            'planned_start' => $startPlanned?->toDateString(),
            'planned_end' => $endPlanned?->toDateString(),
            'notes' => $currentStage?->annotations,
            'editor' => [
                'has_cycle' => $hasCycle,
                'selected_scheme' => $selectedScheme,
                'save_url' => route('panel.animals.wintering.save', $animal->id),
                'schemes' => $schemes,
                'rows' => $editorRows,
                'initial_start_date' => now()->toDateString(),
            ],
        ];
    }

    /**
     * @param Collection<int, Wintering> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildEditorRowsFromCycle(Animal $animal, Collection $rows): array
    {
        return $rows
            ->values()
            ->map(fn (Wintering $row): array => [
                'wintering_id' => (int) $row->id,
                'stage_id' => (int) $row->stage_id,
                'stage_order' => (int) ($row->stage?->order ?? 0),
                'stage_title' => (string) ($row->stage?->title ?? ''),
                'default_duration' => (int) ($row->stage?->duration ?? 0),
                'custom_duration' => $row->custom_duration !== null ? (int) $row->custom_duration : null,
                'planned_start_date' => $row->planned_start_date?->toDateString(),
                'planned_end_date' => $row->planned_end_date?->toDateString(),
                'start_date' => $row->start_date?->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
                'start_url' => route('panel.animals.wintering.stage.start', [$animal->id, $row->id]),
                'end_url' => route('panel.animals.wintering.stage.end', [$animal->id, $row->id]),
            ])
            ->all();
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
}
