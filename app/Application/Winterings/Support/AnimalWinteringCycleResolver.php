<?php

namespace App\Application\Winterings\Support;

use App\Models\Wintering;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnimalWinteringCycleResolver
{
    /**
     * @return Collection<int, Wintering>
     */
    public function resolveCurrentCycleForAnimal(int $animalId): Collection
    {
        $rows = Wintering::query()
            ->with(['stage:id,scheme,order,title,duration'])
            ->where('animal_id', $animalId)
            ->orderByDesc('season')
            ->orderByDesc('id')
            ->get();

        return $this->resolveCurrentCycleFromRows($rows);
    }

    /**
     * @param array<int, int> $animalIds
     * @return array<int, Collection<int, Wintering>>
     */
    public function resolveCurrentCyclesByAnimalIds(array $animalIds): array
    {
        $animalIds = collect($animalIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($animalIds === []) {
            return [];
        }

        $rows = Wintering::query()
            ->with(['stage:id,scheme,order,title,duration'])
            ->whereIn('animal_id', $animalIds)
            ->orderByDesc('season')
            ->orderByDesc('id')
            ->get()
            ->groupBy('animal_id');

        $result = [];
        foreach ($animalIds as $animalId) {
            $result[$animalId] = $this->resolveCurrentCycleFromRows($rows->get($animalId, collect()));
        }

        return $result;
    }

    /**
     * @param array<int, int> $animalIds
     * @return array<int, int>
     */
    public function resolveActiveAnimalIds(array $animalIds): array
    {
        $cycles = $this->resolveCurrentCyclesByAnimalIds($animalIds);
        $active = [];

        foreach ($cycles as $animalId => $rows) {
            if ($this->isCycleActive($rows)) {
                $active[] = (int) $animalId;
            }
        }

        return $active;
    }

    /**
     * @param Collection<int, Wintering> $rows
     */
    public function isCycleActive(Collection $rows): bool
    {
        if ($rows->isEmpty()) {
            return false;
        }

        return $rows->contains(function (Wintering $row): bool {
            $hasAnyDate = $row->start_date !== null
                || $row->planned_start_date !== null
                || $row->planned_end_date !== null;

            return $hasAnyDate && $row->end_date === null;
        });
    }

    /**
     * @param Collection<int, Wintering> $rows
     * @return Collection<int, Wintering>
     */
    private function resolveCurrentCycleFromRows(Collection $rows): Collection
    {
        $rows = $rows
            ->filter(fn (Wintering $row): bool => $row->stage !== null)
            ->values();

        if ($rows->isEmpty()) {
            return collect();
        }

        $groups = $rows->groupBy(function (Wintering $row): string {
            $scheme = trim((string) ($row->stage?->scheme ?? ''));
            return (string) ((int) $row->season) . '|' . $scheme;
        });

        $selectedKey = $this->pickCurrentPeriodGroupKey($groups);

        if ($selectedKey === null) {
            return collect();
        }

        return $groups->get($selectedKey, collect())
            ->sortBy(function (Wintering $row): array {
                return [(int) ($row->stage?->order ?? 999), (int) $row->id];
            })
            ->values();
    }

    /**
     * @param Collection<string, Collection<int, Wintering>> $groups
     */
    private function pickCurrentPeriodGroupKey(Collection $groups): ?string
    {
        $currentYear = (int) now()->year;
        $previousYear = $currentYear - 1;

        $candidates = $groups
            ->map(function (Collection $group, string $key) use ($currentYear, $previousYear): ?array {
                $anchor = $this->groupAnchorDate($group);
                if (!$anchor instanceof CarbonInterface) {
                    return null;
                }

                $year = (int) $anchor->format('Y');
                $month = (int) $anchor->format('n');
                $isCurrentPeriod = $year === $currentYear || ($year === $previousYear && $month >= 9);
                if (!$isCurrentPeriod) {
                    return null;
                }

                return [
                    'key' => $key,
                    'season' => (int) ($group->max('season') ?? 0),
                    'anchor' => Carbon::instance($anchor),
                    'latest_id' => (int) ($group->max('id') ?? 0),
                ];
            })
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortByDesc(fn (array $candidate): array => [
                $candidate['season'],
                $candidate['anchor']->timestamp,
                $candidate['latest_id'],
            ])
            ->first()['key'] ?? null;
    }

    /**
     * @param Collection<int, Wintering> $group
     */
    private function groupAnchorDate(Collection $group): ?CarbonInterface
    {
        $anchors = $group
            ->map(function (Wintering $row): ?CarbonInterface {
                return $this->coalesceRowDate($row);
            })
            ->filter()
            ->sortBy(fn (CarbonInterface $date): int => $date->timestamp)
            ->values();

        return $anchors->first();
    }

    private function coalesceRowDate(Wintering $row): ?CarbonInterface
    {
        if ($row->start_date instanceof CarbonInterface) {
            return $row->start_date;
        }

        if ($row->planned_start_date instanceof CarbonInterface) {
            return $row->planned_start_date;
        }

        if ($row->created_at instanceof CarbonInterface) {
            return Carbon::instance($row->created_at);
        }

        return null;
    }
}
