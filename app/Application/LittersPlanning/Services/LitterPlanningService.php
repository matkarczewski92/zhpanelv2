<?php

namespace App\Application\LittersPlanning\Services;

use App\Models\Litter;
use App\Models\LitterPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LitterPlanningService
{
    public function store(array $data): LitterPlan
    {
        return DB::transaction(function () use ($data): LitterPlan {
            $planId = $data['plan_id'] ?? null;

            $plan = LitterPlan::query()->updateOrCreate(
                ['id' => $planId],
                [
                    'name' => trim((string) ($data['plan_name'] ?? '')),
                    'planned_year' => $data['planned_year'] ?? null,
                ]
            );

            $pairs = $this->normalizePairs((array) ($data['pairs'] ?? []));

            $plan->pairs()->delete();
            foreach ($pairs as $pair) {
                $plan->pairs()->create([
                    'female_id' => $pair['female_id'],
                    'male_id' => $pair['male_id'],
                ]);
            }

            return $plan->fresh(['pairs.female:id,name', 'pairs.male:id,name']) ?? $plan;
        });
    }

    public function delete(LitterPlan $plan): void
    {
        DB::transaction(static function () use ($plan): void {
            $plan->delete();
        });
    }

    public function realize(LitterPlan $plan, ?int $plannedYear = null): int
    {
        return DB::transaction(function () use ($plan, $plannedYear): int {
            $plan->loadMissing('pairs');
            $year = $plannedYear ?? ($plan->planned_year !== null ? (int) $plan->planned_year : (int) now()->format('Y'));

            $created = 0;
            foreach ($plan->pairs as $pair) {
                Litter::query()->create([
                    'category' => 2,
                    'season' => $year,
                    'litter_code' => $this->buildLitterCode((int) $pair->male_id, (int) $pair->female_id, $year),
                    'parent_male' => (int) $pair->male_id,
                    'parent_female' => (int) $pair->female_id,
                ]);
                $created++;
            }

            $plan->planned_year = $year;
            $plan->save();

            return $created;
        });
    }

    /**
     * @param array<int, array{female_id:int, male_id:int}> $pairs
     * @return Collection<int, array{female_id:int, male_id:int}>
     */
    private function normalizePairs(array $pairs): Collection
    {
        return collect($pairs)
            ->filter(fn (mixed $pair): bool => is_array($pair))
            ->map(function (array $pair): array {
                return [
                    'female_id' => (int) ($pair['female_id'] ?? 0),
                    'male_id' => (int) ($pair['male_id'] ?? 0),
                ];
            })
            ->filter(fn (array $pair): bool => $pair['female_id'] > 0 && $pair['male_id'] > 0)
            ->unique(fn (array $pair): string => $pair['female_id'] . ':' . $pair['male_id'])
            ->values();
    }

    private function buildLitterCode(int $maleId, int $femaleId, int $year): string
    {
        if ($year < 2023) {
            return 'PLAN';
        }

        $offset = $year - 2023;
        $letter = chr(ord('A') + $offset);
        $male = str_pad((string) $maleId, 2, '0', STR_PAD_LEFT);
        $female = str_pad((string) $femaleId, 2, '0', STR_PAD_LEFT);

        return "{$letter}.{$male}.{$female}";
    }
}
