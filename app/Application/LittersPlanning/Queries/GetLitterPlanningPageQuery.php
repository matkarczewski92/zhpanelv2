<?php

namespace App\Application\LittersPlanning\Queries;

use App\Application\LittersPlanning\ViewModels\LitterPlanningPageViewModel;
use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use App\Models\Litter;
use App\Models\LitterPlan;
use App\Services\Genetics\GenotypeCalculator;
use Illuminate\Support\Collection;

class GetLitterPlanningPageQuery
{
    public function __construct(private readonly GenotypeCalculator $genotypeCalculator)
    {
    }

    public function handle(array $filters = []): LitterPlanningPageViewModel
    {
        $plans = $this->buildPlans();
        $usedFemaleIds = $this->extractUsedFemaleIds($plans);

        $females = Animal::query()
            ->whereIn('animal_category_id', [1, 4])
            ->where('sex', 3)
            ->withMax('weights', 'value')
            ->orderBy('id')
            ->get(['id', 'name', 'animal_type_id'])
            ->map(function (Animal $animal) use ($usedFemaleIds): array {
                $weight = (int) round((float) ($animal->weights_max_value ?? 0));

                return [
                    'id' => (int) $animal->id,
                    'name' => $this->normalizeName($animal->name),
                    'display_name' => '(' . $weight . 'g.) ' . $this->normalizeName($animal->name),
                    'animal_type_id' => $animal->animal_type_id !== null ? (int) $animal->animal_type_id : null,
                    'weight' => $weight,
                    'color' => $weight < 250 ? 'danger' : ($weight < 300 ? 'warning' : 'success'),
                    'is_used' => in_array((int) $animal->id, $usedFemaleIds, true),
                ];
            })
            ->all();

        $males = Animal::query()
            ->whereIn('animal_category_id', [1, 4])
            ->where('sex', 2)
            ->withMax('weights', 'value')
            ->orderBy('id')
            ->get(['id', 'name', 'animal_type_id'])
            ->map(function (Animal $animal): array {
                $weight = (int) round((float) ($animal->weights_max_value ?? 0));

                return [
                    'id' => (int) $animal->id,
                    'name' => $this->normalizeName($animal->name),
                    'animal_type_id' => $animal->animal_type_id !== null ? (int) $animal->animal_type_id : null,
                    'weight' => $weight,
                    'color' => $weight < 180 ? 'danger' : ($weight < 250 ? 'warning' : 'success'),
                ];
            })
            ->all();

        $seasons = Litter::query()
            ->whereNotNull('season')
            ->select('season')
            ->distinct()
            ->orderBy('season')
            ->pluck('season')
            ->map(fn (mixed $season): int => (int) $season)
            ->filter(fn (int $season): bool => $season > 0)
            ->values()
            ->all();

        $currentYear = (int) now()->format('Y');
        $selectedSeason = isset($filters['season']) ? (int) $filters['season'] : 0;
        if ($selectedSeason <= 0) {
            $selectedSeason = in_array($currentYear, $seasons, true)
                ? $currentYear
                : (!empty($seasons) ? (int) end($seasons) : $currentYear);
        }

        $seasonOffspringRows = $this->buildSeasonOffspringRows($selectedSeason);

        return new LitterPlanningPageViewModel(
            females: $females,
            males: $males,
            plans: $plans,
            seasons: $seasons,
            selectedSeason: $selectedSeason,
            seasonOffspringRows: $seasonOffspringRows,
        );
    }

    /**
     * @return array<int, array{id:int,name:string,planned_year:int|null,updated_at_label:string,pairs:array<int, array{female_id:int,female_name:string,male_id:int,male_name:string}>}>
     */
    private function buildPlans(): array
    {
        return LitterPlan::query()
            ->with([
                'pairs:id,litter_plan_id,female_id,male_id',
                'pairs.female:id,name',
                'pairs.male:id,name',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (LitterPlan $plan): array {
                return [
                    'id' => (int) $plan->id,
                    'name' => trim((string) $plan->name),
                    'planned_year' => $plan->planned_year !== null ? (int) $plan->planned_year : null,
                    'updated_at_label' => $plan->updated_at?->format('Y-m-d H:i') ?? '-',
                    'pairs' => $plan->pairs->map(function ($pair): array {
                        return [
                            'female_id' => (int) $pair->female_id,
                            'female_name' => $this->normalizeName($pair->female?->name, 'Samica #' . $pair->female_id),
                            'male_id' => (int) $pair->male_id,
                            'male_name' => $this->normalizeName($pair->male?->name, 'Samiec #' . $pair->male_id),
                        ];
                    })->values()->all(),
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array{id:int,name:string,planned_year:int|null,updated_at_label:string,pairs:array<int, array{female_id:int,female_name:string,male_id:int,male_name:string}>}> $plans
     * @return array<int, int>
     */
    private function extractUsedFemaleIds(array $plans): array
    {
        return collect($plans)
            ->flatMap(fn (array $plan): array => $plan['pairs'])
            ->pluck('female_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{litter_id:int,litter_code:string,season:int,traits_name:string,visual_traits:array<int, string>,carrier_traits:array<int, string>,traits_count:int,percentage:float,percentage_label:string,litter_url:string}>
     */
    private function buildSeasonOffspringRows(int $season): array
    {
        $litters = Litter::query()
            ->with([
                'maleParent:id,name,animal_type_id',
                'maleParent.genotypes',
                'maleParent.genotypes.category',
                'femaleParent:id,name,animal_type_id',
                'femaleParent.genotypes',
                'femaleParent.genotypes.category',
            ])
            ->where('season', $season)
            ->whereNotNull('parent_male')
            ->whereNotNull('parent_female')
            ->orderBy('id')
            ->get(['id', 'litter_code', 'season', 'parent_male', 'parent_female']);

        if ($litters->isEmpty()) {
            return [];
        }

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $rows = [];
        foreach ($litters as $litter) {
            $male = $litter->maleParent;
            $female = $litter->femaleParent;
            if (!$male || !$female) {
                continue;
            }

            $calculatedRows = $this->genotypeCalculator
                ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
                ->getGenotypeFinale(
                    $this->buildAnimalGenotypeArray($male),
                    $this->buildAnimalGenotypeArray($female),
                    $dictionary
                );

            foreach ($calculatedRows as $row) {
                $percentage = (float) ($row['percentage'] ?? 0);
                $rows[] = [
                    'litter_id' => (int) $litter->id,
                    'litter_code' => (string) $litter->litter_code,
                    'season' => (int) $litter->season,
                    'traits_name' => trim((string) ($row['traits_name'] ?? '')),
                    'visual_traits' => array_values((array) ($row['visual_traits'] ?? [])),
                    'carrier_traits' => array_values((array) ($row['carrier_traits'] ?? [])),
                    'traits_count' => (int) ($row['traits_count'] ?? 0),
                    'percentage' => $percentage,
                    'percentage_label' => number_format($percentage, 2, ',', ' ') . '%',
                    'litter_url' => route('panel.litters.show', $litter->id),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function buildAnimalGenotypeArray(Animal $animal): array
    {
        $result = [];

        foreach ($animal->genotypes as $genotype) {
            $type = strtolower((string) ($genotype->type ?? ''));
            if ($type === 'p' || ($type !== 'h' && $type !== 'v')) {
                continue;
            }

            $category = $genotype->category;
            if (!$category) {
                continue;
            }

            $geneCode = (string) ($category->gene_code ?? '');
            $geneType = strtolower((string) ($category->gene_type ?? ''));
            if ($geneCode === '') {
                continue;
            }

            if ($type === 'h') {
                $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
                continue;
            }

            if ($geneType === 'r') {
                $result[] = [lcfirst($geneCode), lcfirst($geneCode)];
            } elseif ($geneType === 'd' || $geneType === 'i') {
                $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
            } else {
                $result[] = [ucfirst($geneCode), ucfirst($geneCode)];
            }
        }

        return $result;
    }

    private function normalizeName(?string $value, string $fallback = '-'): string
    {
        $name = trim(strip_tags((string) $value));

        return $name !== '' ? $name : $fallback;
    }
}
