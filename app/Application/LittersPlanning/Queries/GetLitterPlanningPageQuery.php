<?php

namespace App\Application\LittersPlanning\Queries;

use App\Application\LittersPlanning\ViewModels\LitterPlanningPageViewModel;
use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalGenotypeTrait;
use App\Models\Litter;
use App\Models\LitterPlan;
use App\Models\LitterRoadmap;
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
        $savedRoadmapModels = LitterRoadmap::query()
            ->orderByDesc('updated_at')
            ->get();
        $savedRoadmaps = $this->buildSavedRoadmaps($savedRoadmapModels);
        $roadmapKeeperRows = $this->buildRoadmapKeeperRows($savedRoadmapModels);
        $usedFemaleIds = $this->extractUsedFemaleIds($plans);
        $selectedRoadmapId = isset($filters['roadmap_id']) && is_numeric($filters['roadmap_id'])
            ? (int) $filters['roadmap_id']
            : 0;
        $openedRoadmapId = isset($filters['roadmap_open_id']) && is_numeric($filters['roadmap_open_id'])
            ? (int) $filters['roadmap_open_id']
            : 0;
        $selectedRoadmap = $selectedRoadmapId > 0
            ? LitterRoadmap::query()->find($selectedRoadmapId)
            : null;
        $connectionSearchInput = trim((string) ($filters['expected_genes'] ?? ''));
        $traitGeneAliasMap = $this->buildTraitGeneAliasMap();
        $connectionExpectedTraits = $this->parseExpectedTraits($connectionSearchInput, $traitGeneAliasMap);
        $strictVisualOnlyFilter = $filters['strict_visual_only'] ?? null;
        $connectionStrictVisualOnly = $strictVisualOnlyFilter === null
            ? true
            : (bool) $strictVisualOnlyFilter;
        $connectionGeneSuggestions = $this->buildConnectionGeneSuggestions($traitGeneAliasMap);
        $hasRoadmapManualInput = isset($filters['roadmap_expected_genes'])
            && trim((string) ($filters['roadmap_expected_genes'] ?? '')) !== '';
        $roadmapSearchInput = trim((string) ($filters['roadmap_expected_genes'] ?? ''));
        $roadmapPriorityMode = (string) ($filters['roadmap_priority_mode'] ?? 'fastest');
        if (!in_array($roadmapPriorityMode, ['fastest', 'highest_probability'], true)) {
            $roadmapPriorityMode = 'fastest';
        }
        $roadmapExcludedRootPairs = $this->parseRootPairKeys((string) ($filters['roadmap_excluded_root_pairs'] ?? ''));
        $roadmapGenerations = isset($filters['roadmap_generations']) && is_numeric($filters['roadmap_generations'])
            ? (int) $filters['roadmap_generations']
            : 0; // 0 = dowolny

        if ($selectedRoadmap && $roadmapSearchInput === '') {
            $roadmapSearchInput = trim((string) ($selectedRoadmap->search_input ?? ''));
            $roadmapGenerations = (int) ($selectedRoadmap->generations ?? 0);
        }

        $roadmapExpectedTraits = $this->parseExpectedTraits($roadmapSearchInput, $traitGeneAliasMap);

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
        $connectionSearchRows = [];
        $connectionCheckedPairs = 0;
        $roadmapTargetReachable = false;
        $roadmapMatchedTraits = [];
        $roadmapMissingTraits = $roadmapExpectedTraits;
        $roadmapSteps = [];
        $roadmapRootPairKey = '';

        if (!empty($connectionExpectedTraits)) {
            $connectionSearchRows = $this->buildConnectionSearchRows(
                $connectionExpectedTraits,
                $connectionStrictVisualOnly,
                $connectionCheckedPairs
            );
        }

        if ($selectedRoadmap && $roadmapSearchInput !== '' && !$hasRoadmapManualInput) {
            $completedGenerations = collect((array) ($selectedRoadmap->completed_generations ?? []))
                ->map(fn (mixed $generation): int => (int) $generation)
                ->filter(fn (int $generation): bool => $generation > 0)
                ->unique()
                ->values()
                ->all();

            $roadmapTargetReachable = (bool) ($selectedRoadmap->target_reachable ?? false);
            $roadmapMatchedTraits = array_values((array) ($selectedRoadmap->matched_traits ?? []));
            $roadmapMissingTraits = array_values((array) ($selectedRoadmap->missing_traits ?? []));
            $roadmapSteps = $this->applyRoadmapRealizedFlags(
                array_values((array) ($selectedRoadmap->steps ?? [])),
                $completedGenerations
            );
            $roadmapRootPairKey = $this->extractRootPairKeyFromSteps($roadmapSteps);
        } elseif (!empty($roadmapExpectedTraits)) {
            $roadmap = $this->buildRoadmapSnapshot(
                $roadmapSearchInput,
                $roadmapGenerations,
                $connectionStrictVisualOnly,
                $roadmapPriorityMode,
                $roadmapExcludedRootPairs
            );
            $roadmapTargetReachable = $roadmap['target_reachable'];
            $roadmapMatchedTraits = $roadmap['matched_traits'];
            $roadmapMissingTraits = $roadmap['missing_traits'];
            $roadmapSteps = $roadmap['steps'];
            $roadmapRootPairKey = trim((string) ($roadmap['root_pair_key'] ?? ''));
        }

        $roadmapSteps = $this->normalizeRoadmapStepsForDisplay($roadmapSteps);
        $roadmapSteps = $this->enrichRoadmapStepsWithExistingLitters($roadmapSteps, $currentYear);

        return new LitterPlanningPageViewModel(
            females: $females,
            males: $males,
            plans: $plans,
            seasons: $seasons,
            selectedSeason: $selectedSeason,
            seasonOffspringRows: $seasonOffspringRows,
            connectionSearchInput: $connectionSearchInput,
            connectionExpectedTraits: $connectionExpectedTraits,
            connectionStrictVisualOnly: $connectionStrictVisualOnly,
            connectionGeneSuggestions: $connectionGeneSuggestions,
            connectionCheckedPairs: $connectionCheckedPairs,
            connectionSearchRows: $connectionSearchRows,
            roadmapSearchInput: $roadmapSearchInput,
            roadmapPriorityMode: $roadmapPriorityMode,
            roadmapExcludedRootPairs: $roadmapExcludedRootPairs,
            roadmapRootPairKey: $roadmapRootPairKey,
            roadmapGenerations: $roadmapGenerations,
            roadmapExpectedTraits: $roadmapExpectedTraits,
            roadmapTargetReachable: $roadmapTargetReachable,
            roadmapMatchedTraits: $roadmapMatchedTraits,
            roadmapMissingTraits: $roadmapMissingTraits,
            roadmapSteps: $roadmapSteps,
            roadmaps: $savedRoadmaps,
            activeRoadmapId: $selectedRoadmapId > 0 ? $selectedRoadmapId : $openedRoadmapId,
            roadmapKeepers: $roadmapKeeperRows,
        );
    }

    /**
     * @return array{
     *     search_input:string,
     *     generations:int,
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array<string, mixed>>
     * }
     */
    public function buildRoadmapSnapshot(
        string $searchInput,
        int $generations = 0,
        bool $strictVisualOnly = true,
        string $priorityMode = 'fastest',
        array $excludedGenerationOnePairs = []
    ): array
    {
        $normalizedSearch = trim($searchInput);
        $traitGeneAliasMap = $this->buildTraitGeneAliasMap();
        $expectedTraits = $this->parseExpectedTraits($normalizedSearch, $traitGeneAliasMap);
        $generationsNormalized = $generations >= 2 && $generations <= 5 ? $generations : 0;
        $generationsLimit = $generationsNormalized > 0 ? $generationsNormalized : 5;
        if (!in_array($priorityMode, ['fastest', 'highest_probability'], true)) {
            $priorityMode = 'fastest';
        }

        $roadmap = !empty($expectedTraits)
            ? $this->buildRoadmap(
                $expectedTraits,
                $generationsLimit,
                $strictVisualOnly,
                $priorityMode,
                $excludedGenerationOnePairs
            )
            : [
                'target_reachable' => false,
                'matched_traits' => [],
                'missing_traits' => [],
                'steps' => [],
                'root_pair_key' => '',
            ];

        return [
            'search_input' => $normalizedSearch,
            'generations' => $generationsNormalized,
            'expected_traits' => $expectedTraits,
            'target_reachable' => (bool) ($roadmap['target_reachable'] ?? false),
            'matched_traits' => array_values((array) ($roadmap['matched_traits'] ?? [])),
            'missing_traits' => array_values((array) ($roadmap['missing_traits'] ?? [])),
            'steps' => array_values((array) ($roadmap['steps'] ?? [])),
            'root_pair_key' => trim((string) ($roadmap['root_pair_key'] ?? '')),
        ];
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
     * @return array<int, array{
     *     id:int,
     *     name:string,
     *     search_input:string,
     *     generations:int,
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     completed_generations:array<int, int>,
     *     steps_count:int,
     *     last_refreshed_at_label:string,
     *     updated_at_label:string
     * }>
     */
    private function buildSavedRoadmaps(Collection $roadmaps): array
    {
        return $roadmaps
            ->map(function (LitterRoadmap $roadmap): array {
                return [
                    'id' => (int) $roadmap->id,
                    'name' => trim((string) $roadmap->name),
                    'search_input' => trim((string) $roadmap->search_input),
                    'generations' => (int) ($roadmap->generations ?? 0),
                    'expected_traits' => array_values((array) ($roadmap->expected_traits ?? [])),
                    'target_reachable' => (bool) ($roadmap->target_reachable ?? false),
                    'matched_traits' => array_values((array) ($roadmap->matched_traits ?? [])),
                    'missing_traits' => array_values((array) ($roadmap->missing_traits ?? [])),
                    'completed_generations' => collect((array) ($roadmap->completed_generations ?? []))
                        ->map(fn (mixed $generation): int => (int) $generation)
                        ->filter(fn (int $generation): bool => $generation > 0)
                        ->unique()
                        ->values()
                        ->all(),
                    'steps_count' => count((array) ($roadmap->steps ?? [])),
                    'last_refreshed_at_label' => $roadmap->last_refreshed_at?->format('Y-m-d H:i') ?? '-',
                    'updated_at_label' => $roadmap->updated_at?->format('Y-m-d H:i') ?? '-',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     * @param array<int, int> $completedGenerations
     * @return array<int, array<string, mixed>>
     */
    private function applyRoadmapRealizedFlags(array $steps, array $completedGenerations): array
    {
        return collect($steps)
            ->map(function (array $step) use ($completedGenerations): array {
                $generation = (int) ($step['generation'] ?? 0);
                $step['is_realized'] = $generation > 0 && in_array($generation, $completedGenerations, true);

                return $step;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRoadmapStepsForDisplay(array $steps): array
    {
        return collect($steps)
            ->map(function (array $step): array {
                $offspringRows = collect((array) ($step['offspring_rows'] ?? []))
                    ->map(function (mixed $row): array {
                        if (!is_array($row)) {
                            return [];
                        }

                        $row['carrier_traits'] = $this->sortCarrierTraitsForDisplay(
                            collect((array) ($row['carrier_traits'] ?? []))
                                ->map(fn (mixed $trait): string => trim((string) $trait))
                                ->filter()
                                ->values()
                                ->all()
                        );

                        return $row;
                    })
                    ->filter(fn (array $row): bool => !empty($row))
                    ->values()
                    ->all();

                $keeperLabels = collect($offspringRows)
                    ->filter(fn (array $row): bool => (bool) ($row['is_keeper'] ?? false))
                    ->map(function (array $row): string {
                        $name = trim((string) ($row['traits_name'] ?? ''));
                        if ($name !== '') {
                            return $name;
                        }

                        $visual = collect((array) ($row['visual_traits'] ?? []))
                            ->map(fn (mixed $trait): string => trim((string) $trait))
                            ->filter()
                            ->values()
                            ->all();
                        $carrier = $this->sortCarrierTraitsForDisplay(
                            collect((array) ($row['carrier_traits'] ?? []))
                                ->map(fn (mixed $trait): string => trim((string) $trait))
                                ->filter()
                                ->values()
                                ->all()
                        );

                        $parts = array_merge($visual, $carrier);

                        return !empty($parts) ? implode(', ', $parts) : '-';
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $hasTargetRow = collect($offspringRows)
                    ->contains(fn (array $row): bool => (bool) ($row['is_target'] ?? false));

                if ($hasTargetRow) {
                    $offspringRows = collect($offspringRows)
                        ->map(function (array $row): array {
                            $row['is_keeper'] = false;

                            return $row;
                        })
                        ->values()
                        ->all();
                }

                $step['offspring_rows'] = $offspringRows;
                $step['has_target_row'] = $hasTargetRow;
                if ($hasTargetRow) {
                    $step['keeper_label'] = '';
                } elseif (!empty($keeperLabels)) {
                    $step['keeper_label'] = implode(' + ', $keeperLabels);
                } else {
                    $step['keeper_label'] = '-';
                }

                return $step;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     * @return array<int, array<string, mixed>>
     */
    private function enrichRoadmapStepsWithExistingLitters(array $steps, int $minSeason): array
    {
        if (empty($steps)) {
            return [];
        }

        $pairs = [];
        foreach ($steps as $index => $step) {
            $canCreate = (bool) ($step['can_create_litter'] ?? false);
            $maleId = isset($step['parent_male_id']) ? (int) ($step['parent_male_id'] ?? 0) : 0;
            $femaleId = isset($step['parent_female_id']) ? (int) ($step['parent_female_id'] ?? 0) : 0;

            if (!$canCreate || $maleId <= 0 || $femaleId <= 0) {
                continue;
            }

            $pairs[$maleId . ':' . $femaleId] = [
                'parent_male' => $maleId,
                'parent_female' => $femaleId,
            ];
        }

        $littersByPair = [];
        if (!empty($pairs)) {
            $pairValues = array_values($pairs);
            $litters = Litter::query()
                ->where('season', '>=', $minSeason)
                ->where(function ($query) use ($pairValues): void {
                    foreach ($pairValues as $pair) {
                        $query->orWhere(function ($pairQuery) use ($pair): void {
                            $pairQuery
                                ->where('parent_male', (int) ($pair['parent_male'] ?? 0))
                                ->where('parent_female', (int) ($pair['parent_female'] ?? 0));
                        });
                    }
                })
                ->orderByDesc('season')
                ->orderByDesc('id')
                ->get(['id', 'litter_code', 'season', 'parent_male', 'parent_female']);

            foreach ($litters as $litter) {
                $key = (int) $litter->parent_male . ':' . (int) $litter->parent_female;
                if (isset($littersByPair[$key])) {
                    continue;
                }

                $littersByPair[$key] = [
                    'id' => (int) $litter->id,
                    'code' => trim((string) $litter->litter_code),
                    'season' => (int) ($litter->season ?? 0),
                ];
            }
        }

        return collect($steps)
            ->map(function (array $step) use ($littersByPair): array {
                $maleId = isset($step['parent_male_id']) ? (int) ($step['parent_male_id'] ?? 0) : 0;
                $femaleId = isset($step['parent_female_id']) ? (int) ($step['parent_female_id'] ?? 0) : 0;
                $pairKey = $maleId . ':' . $femaleId;
                $linkedLitter = ($maleId > 0 && $femaleId > 0) ? ($littersByPair[$pairKey] ?? null) : null;

                $step['existing_litter_id'] = $linkedLitter ? (int) ($linkedLitter['id'] ?? 0) : null;
                $step['existing_litter_code'] = $linkedLitter ? (string) ($linkedLitter['code'] ?? '') : null;
                $step['existing_litter_season'] = $linkedLitter ? (int) ($linkedLitter['season'] ?? 0) : null;
                $step['existing_litter_url'] = $linkedLitter
                    ? route('panel.litters.show', (int) ($linkedLitter['id'] ?? 0))
                    : null;

                return $step;
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, LitterRoadmap> $roadmaps
     * @return array<int, array{
     *     roadmap_id:int,
     *     roadmap_name:string,
     *     generation:int,
     *     pairing_label:string,
     *     keeper_label:string,
     *     parent_male_id:int|null,
     *     parent_female_id:int|null,
     *     litter_id:int|null,
     *     litter_code:string|null,
     *     litter_url:string|null
     * }>
     */
    private function buildRoadmapKeeperRows(Collection $roadmaps): array
    {
        if ($roadmaps->isEmpty()) {
            return [];
        }

        $rows = [];
        $pairsToResolve = [];

        foreach ($roadmaps as $roadmap) {
            $steps = collect((array) ($roadmap->steps ?? []))
                ->filter(fn (mixed $step): bool => is_array($step))
                ->values();

            foreach ($steps as $step) {
                $generation = (int) ($step['generation'] ?? 0);
                $pairingLabel = trim((string) ($step['pairing_label'] ?? '-'));
                $parentMaleId = isset($step['parent_male_id']) ? (int) $step['parent_male_id'] : 0;
                $parentFemaleId = isset($step['parent_female_id']) ? (int) $step['parent_female_id'] : 0;
                $canCreateLitter = (bool) ($step['can_create_litter'] ?? false)
                    && $parentMaleId > 0
                    && $parentFemaleId > 0;

                if ($canCreateLitter) {
                    $pairsToResolve[$parentMaleId . ':' . $parentFemaleId] = [
                        'parent_male' => $parentMaleId,
                        'parent_female' => $parentFemaleId,
                    ];
                }

                // If this step already contains the final target row, we should
                // not suggest any "keeper" from this generation in "Do zostawienia".
                $hasTargetRow = collect((array) ($step['offspring_rows'] ?? []))
                    ->contains(fn (mixed $row): bool => is_array($row) && (bool) ($row['is_target'] ?? false));
                if ($hasTargetRow) {
                    continue;
                }

                $keeperRows = collect((array) ($step['offspring_rows'] ?? []))
                    ->filter(fn (mixed $row): bool => is_array($row) && (bool) ($row['is_keeper'] ?? false))
                    ->values();

                foreach ($keeperRows as $keeperRow) {
                    $rows[] = [
                        'roadmap_id' => (int) $roadmap->id,
                        'roadmap_name' => trim((string) $roadmap->name),
                        'generation' => $generation,
                        'pairing_label' => $pairingLabel !== '' ? $pairingLabel : '-',
                        'keeper_label' => $this->formatRoadmapKeeperLabel((array) $keeperRow),
                        'parent_male_id' => $canCreateLitter ? $parentMaleId : null,
                        'parent_female_id' => $canCreateLitter ? $parentFemaleId : null,
                        'litter_id' => null,
                        'litter_code' => null,
                        'litter_url' => null,
                    ];
                }
            }
        }

        $littersByPair = [];
        if (!empty($pairsToResolve)) {
            $pairs = array_values($pairsToResolve);
            $litters = Litter::query()
                ->where(function ($query) use ($pairs): void {
                    foreach ($pairs as $pair) {
                        $query->orWhere(function ($pairQuery) use ($pair): void {
                            $pairQuery
                                ->where('parent_male', (int) ($pair['parent_male'] ?? 0))
                                ->where('parent_female', (int) ($pair['parent_female'] ?? 0));
                        });
                    }
                })
                ->orderByDesc('id')
                ->get(['id', 'litter_code', 'parent_male', 'parent_female']);

            foreach ($litters as $litter) {
                $key = (int) $litter->parent_male . ':' . (int) $litter->parent_female;
                if (isset($littersByPair[$key])) {
                    continue;
                }

                $littersByPair[$key] = [
                    'id' => (int) $litter->id,
                    'code' => trim((string) $litter->litter_code),
                ];
            }
        }

        return collect($rows)
            ->map(function (array $row) use ($littersByPair): array {
                $maleId = isset($row['parent_male_id']) ? (int) ($row['parent_male_id'] ?? 0) : 0;
                $femaleId = isset($row['parent_female_id']) ? (int) ($row['parent_female_id'] ?? 0) : 0;
                if ($maleId <= 0 || $femaleId <= 0) {
                    return $row;
                }

                $key = $maleId . ':' . $femaleId;
                $linkedLitter = $littersByPair[$key] ?? null;
                if (!$linkedLitter) {
                    return $row;
                }

                $row['litter_id'] = (int) ($linkedLitter['id'] ?? 0);
                $row['litter_code'] = (string) ($linkedLitter['code'] ?? '');
                $row['litter_url'] = route('panel.litters.show', $row['litter_id']);

                return $row;
            })
            ->sort(function (array $a, array $b): int {
                $nameCompare = strcmp((string) ($a['roadmap_name'] ?? ''), (string) ($b['roadmap_name'] ?? ''));
                if ($nameCompare !== 0) {
                    return $nameCompare;
                }

                $genA = (int) ($a['generation'] ?? 0);
                $genB = (int) ($b['generation'] ?? 0);
                if ($genA === $genB) {
                    return strcmp((string) ($a['keeper_label'] ?? ''), (string) ($b['keeper_label'] ?? ''));
                }

                return $genA <=> $genB;
            })
            ->values()
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
                    'carrier_traits' => $this->sortCarrierTraitsForDisplay(array_values((array) ($row['carrier_traits'] ?? []))),
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

    /**
     * @return array<int, string>
     */
    private function parseExpectedTraits(string $input, array $traitGeneAliasMap = []): array
    {
        if ($input === '') {
            return [];
        }

        $parts = preg_split('/[,;\n\r]+/', $input) ?: [];

        return collect($parts)
            ->map(function (string $part): string {
                $normalized = strtolower(trim($part));
                $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

                return trim($normalized);
            })
            ->filter()
            ->flatMap(function (string $part) use ($traitGeneAliasMap): array {
                if (str_starts_with($part, 'het ')) {
                    return [$part];
                }

                $expanded = $traitGeneAliasMap[$part] ?? null;

                return is_array($expanded) && !empty($expanded) ? $expanded : [$part];
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function buildConnectionGeneSuggestions(array $traitGeneAliasMap = []): array
    {
        $geneSuggestions = AnimalGenotypeCategory::query()
            ->whereNotNull('name')
            ->select('name')
            ->orderBy('name')
            ->get()
            ->map(fn (AnimalGenotypeCategory $category): string => trim((string) $category->name))
            ->filter()
            ->values()
            ->all();

        $traitSuggestions = AnimalGenotypeTrait::query()
            ->whereNotNull('name')
            ->select('name')
            ->orderBy('name')
            ->get()
            ->map(fn (AnimalGenotypeTrait $trait): string => trim((string) $trait->name))
            ->filter();

        return collect($geneSuggestions)
            ->merge($traitSuggestions)
            ->filter()
            ->map(fn (string $value): string => trim($value))
            ->unique(fn (string $value): string => strtolower($value))
            ->sortBy(fn (string $value): string => strtolower($value))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildTraitGeneAliasMap(): array
    {
        return AnimalGenotypeTrait::query()
            ->with(['genes.category:id,name'])
            ->get(['id', 'name'])
            ->mapWithKeys(function (AnimalGenotypeTrait $trait): array {
                $traitName = $this->normalizeTrait((string) $trait->name);
                if ($traitName === '') {
                    return [];
                }

                $genes = $trait->genes
                    ->map(function ($dictionaryRow): string {
                        $geneName = (string) ($dictionaryRow->category?->name ?? '');

                        return $this->normalizeTrait($geneName);
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($genes)) {
                    return [];
                }

                return [$traitName => $genes];
            })
            ->all();
    }

    /**
     * @param array<int, string> $expectedTraits
     * @param int $checkedPairs
     * @return array<int, array{
     *     female_id:int,
     *     female_name:string,
     *     male_id:int,
     *     male_name:string,
     *     probability:float,
     *     probability_label:string,
     *     matched_rows_count:int,
     *     matched_rows:array<int, array{
     *         percentage:float,
     *         percentage_label:string,
     *         traits_name:string,
     *         visual_traits:array<int, string>,
     *         carrier_traits:array<int, string>
     *     }>
     * }>
     */
    private function buildConnectionSearchRows(
        array $expectedTraits,
        bool $strictVisualOnly,
        int &$checkedPairs
    ): array
    {
        $animals = Animal::query()
            ->where('animal_category_id', 1)
            ->whereIn('sex', [2, 3])
            ->with(['genotypes.category'])
            ->orderBy('id')
            ->get(['id', 'name', 'sex', 'animal_type_id']);

        $females = $animals
            ->filter(fn (Animal $animal): bool => (int) $animal->sex === 3)
            ->values();
        $malesByType = $animals
            ->filter(fn (Animal $animal): bool => (int) $animal->sex === 2)
            ->groupBy(fn (Animal $animal): string => (string) ($animal->animal_type_id ?? '0'));

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $results = [];
        foreach ($females as $female) {
            $femaleTypeId = $female->animal_type_id !== null ? (int) $female->animal_type_id : 0;
            if ($femaleTypeId <= 0) {
                continue;
            }

            /** @var Collection<int, Animal> $typeMales */
            $typeMales = $malesByType->get((string) $femaleTypeId, collect());
            foreach ($typeMales as $male) {
                $checkedPairs++;

                $rows = $this->genotypeCalculator
                    ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
                    ->getGenotypeFinale(
                        $this->buildAnimalGenotypeArray($male),
                        $this->buildAnimalGenotypeArray($female),
                        $dictionary
                    );

                $matchedRows = collect($rows)
                    ->filter(fn (array $row): bool => $this->rowMatchesExpectedTraits($row, $expectedTraits, $strictVisualOnly))
                    ->map(fn (array $row): array => $this->mapMatchedRow($row))
                    ->sortByDesc('percentage')
                    ->values();

                $probability = (float) $matchedRows->sum('percentage');
                if ($probability <= 0) {
                    continue;
                }

                $results[] = [
                    'female_id' => (int) $female->id,
                    'female_name' => $this->normalizeName($female->name, 'Samica #' . $female->id),
                    'male_id' => (int) $male->id,
                    'male_name' => $this->normalizeName($male->name, 'Samiec #' . $male->id),
                    'probability' => $probability,
                    'probability_label' => number_format($probability, 2, ',', ' ') . '%',
                    'matched_rows_count' => $matchedRows->count(),
                    'matched_rows' => $matchedRows->take(5)->all(),
                ];
            }
        }

        return collect($results)
            ->sort(function (array $a, array $b): int {
                if ($a['probability'] === $b['probability']) {
                    $femaleCompare = strcmp($a['female_name'], $b['female_name']);
                    if ($femaleCompare !== 0) {
                        return $femaleCompare;
                    }

                    return strcmp($a['male_name'], $b['male_name']);
                }

                return $a['probability'] < $b['probability'] ? 1 : -1;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $expectedTraits
     * @return array{
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array{
     *         generation:int,
     *         pairing_label:string,
     *         keeper_label:string,
     *         probability_label:string,
     *         can_create_litter:bool,
     *         parent_male_id:int|null,
     *         parent_female_id:int|null,
     *         matched_targets:array<int, string>,
     *         matched_count:int,
     *         total_targets:int,
     *         offspring_rows:array<int, array{
     *             is_keeper:bool,
     *             is_target:bool,
     *             percentage_label:string,
     *             traits_name:string,
     *             visual_traits:array<int, string>,
     *             carrier_traits:array<int, string>,
     *             matched_targets:array<int, string>
     *         }>
     *     }>
     * }
     */
    private function buildRoadmap(
        array $expectedTraits,
        int $maxGenerations,
        bool $strictVisualOnly,
        string $priorityMode,
        array $excludedGenerationOnePairs = [],
        bool $allowDiversification = true
    ): array
    {
        $breeders = Animal::query()
            ->where('animal_category_id', 1)
            ->whereIn('sex', [2, 3])
            ->with(['genotypes.category'])
            ->orderBy('id')
            ->get(['id', 'name', 'sex', 'animal_type_id']);

        if ($breeders->isEmpty()) {
            return [
                'target_reachable' => false,
                'matched_traits' => [],
                'missing_traits' => $expectedTraits,
                'steps' => [],
                'root_pair_key' => '',
            ];
        }

        $maxKeepersPerGeneration = 24;
        $maxKeepersPerPair = 2;
        $maxRowsPerStep = 20;
        $maxMalesPerType = 10;
        $maxFemalesPerType = 10;
        $maxPairChecksPerType = 90;

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $categoriesByName = AnimalGenotypeCategory::query()
            ->get(['name', 'gene_code', 'gene_type'])
            ->keyBy(fn (AnimalGenotypeCategory $category): string => $this->normalizeTrait((string) $category->name));
        $expectedGeneCodes = collect($expectedTraits)
            ->map(fn (string $trait): string => $this->normalizeTrait((string) preg_replace('/^het\s+/i', '', $trait)))
            ->filter()
            ->map(function (string $traitName) use ($categoriesByName): string {
                /** @var AnimalGenotypeCategory|null $category */
                $category = $categoriesByName->get($traitName);
                return strtolower((string) ($category?->gene_code ?? ''));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $scoreGenotypeRelevance = function (array $genotype) use ($expectedGeneCodes): float {
            if (empty($expectedGeneCodes)) {
                return 0.0;
            }

            $score = 0.0;
            foreach ($expectedGeneCodes as $geneCode) {
                foreach ($genotype as $pair) {
                    $left = strtolower((string) ($pair[0] ?? ''));
                    $right = strtolower((string) ($pair[1] ?? ''));
                    if ($left !== $geneCode && $right !== $geneCode) {
                        continue;
                    }

                    $score += 1.0;
                    if ($left === $geneCode && $right === $geneCode) {
                        $score += 0.5;
                    }
                    break;
                }
            }

            return $score;
        };
        $allBreeders = [];
        $realBreederIds = [];
        foreach ($breeders as $animal) {
            $typeId = (int) ($animal->animal_type_id ?? 0);
            if ($typeId <= 0) {
                continue;
            }

            $sexCode = (int) ($animal->sex ?? 0);
            if (!in_array($sexCode, [2, 3], true)) {
                continue;
            }

            $breederId = 'a:' . (int) $animal->id;
            $genotype = $this->buildAnimalGenotypeArray($animal);
            $allBreeders[$breederId] = [
                'id' => $breederId,
                'label' => $this->normalizeName($animal->name),
                'type_id' => $typeId,
                'sex_mode' => $sexCode === 2 ? 'male' : 'female',
                'source_generation' => 0,
                'origin_step_id' => null,
                'origin_row_signature' => null,
                'genotype' => $genotype,
                'relevance' => $scoreGenotypeRelevance($genotype),
            ];
            $realBreederIds[] = $breederId;
        }

        $generatedBreederIds = [];
        $stepRecords = [];
        $stepCounter = 0;
        $bestGoal = null;
        $firstGoalGeneration = null;
        $bestProgress = null;
        $steps = [];

        for ($generation = 1; $generation <= $maxGenerations; $generation++) {
            $availableBreederIds = array_values(array_filter(
                array_keys($allBreeders),
                fn (string $id): bool => (int) ($allBreeders[$id]['source_generation'] ?? 0) < $generation
            ));

            $focusBreederIds = array_values(array_filter(
                $availableBreederIds,
                fn (string $id): bool => (int) ($allBreeders[$id]['source_generation'] ?? 0) === ($generation - 1)
            ));

            if ($generation > 1 && empty($focusBreederIds)) {
                break;
            }

            $maleIdsByType = [];
            $femaleIdsByType = [];
            foreach ($availableBreederIds as $breederId) {
                $breeder = $allBreeders[$breederId];
                $typeId = (int) ($breeder['type_id'] ?? 0);
                if ($typeId <= 0) {
                    continue;
                }

                $sexMode = (string) ($breeder['sex_mode'] ?? 'either');
                if ($sexMode === 'male' || $sexMode === 'either') {
                    $maleIdsByType[$typeId][] = $breederId;
                }
                if ($sexMode === 'female' || $sexMode === 'either') {
                    $femaleIdsByType[$typeId][] = $breederId;
                }
            }

            $newKeepers = [];
            foreach ($maleIdsByType as $typeId => $maleIds) {
                $femaleIds = $femaleIdsByType[$typeId] ?? [];
                if (empty($femaleIds)) {
                    continue;
                }

                usort($maleIds, function (string $aId, string $bId) use ($allBreeders): int {
                    $aRel = (float) ($allBreeders[$aId]['relevance'] ?? 0);
                    $bRel = (float) ($allBreeders[$bId]['relevance'] ?? 0);
                    if ($aRel === $bRel) {
                        return strcmp($aId, $bId);
                    }

                    return $aRel < $bRel ? 1 : -1;
                });
                usort($femaleIds, function (string $aId, string $bId) use ($allBreeders): int {
                    $aRel = (float) ($allBreeders[$aId]['relevance'] ?? 0);
                    $bRel = (float) ($allBreeders[$bId]['relevance'] ?? 0);
                    if ($aRel === $bRel) {
                        return strcmp($aId, $bId);
                    }

                    return $aRel < $bRel ? 1 : -1;
                });

                $maleFocus = array_values(array_filter($maleIds, fn (string $id): bool => in_array($id, $focusBreederIds, true)));
                $femaleFocus = array_values(array_filter($femaleIds, fn (string $id): bool => in_array($id, $focusBreederIds, true)));

                $maleIds = array_values(array_unique(array_merge($maleFocus, array_slice($maleIds, 0, $maxMalesPerType))));
                $femaleIds = array_values(array_unique(array_merge($femaleFocus, array_slice($femaleIds, 0, $maxFemalesPerType))));
                $checkedPairs = 0;

                $focusMap = array_fill_keys($focusBreederIds, true);
                $pairCandidates = [];
                foreach ($maleIds as $maleId) {
                    foreach ($femaleIds as $femaleId) {
                        if ($maleId === $femaleId) {
                            $sameBreederId = (string) ($allBreeders[$maleId]['id'] ?? '');
                            $isVirtualSiblingCross = str_starts_with($sameBreederId, 'v:');
                            if (!$isVirtualSiblingCross) {
                                continue;
                            }
                        }

                        $maleInFocus = isset($focusMap[$maleId]);
                        $femaleInFocus = isset($focusMap[$femaleId]);
                        if ($generation > 1 && !$maleInFocus && !$femaleInFocus) {
                            continue;
                        }

                        $pairCandidates[] = [
                            'male_id' => $maleId,
                            'female_id' => $femaleId,
                        ];
                    }
                }

                usort($pairCandidates, function (array $a, array $b) use ($allBreeders): int {
                    $aRel = (float) (($allBreeders[(string) $a['male_id']]['relevance'] ?? 0) + ($allBreeders[(string) $a['female_id']]['relevance'] ?? 0));
                    $bRel = (float) (($allBreeders[(string) $b['male_id']]['relevance'] ?? 0) + ($allBreeders[(string) $b['female_id']]['relevance'] ?? 0));
                    if ($aRel === $bRel) {
                        $aKey = (string) $a['female_id'] . ':' . (string) $a['male_id'];
                        $bKey = (string) $b['female_id'] . ':' . (string) $b['male_id'];

                        return strcmp($aKey, $bKey);
                    }

                    return $aRel < $bRel ? 1 : -1;
                });

                $pairCheckLimit = $maxPairChecksPerType;
                if ($priorityMode === 'highest_probability') {
                    // W trybie "Najwiekszy % celu" sprawdzamy wszystkie pary
                    // (bez priorytetow), zeby nie gubic rozwiazan przez limit.
                    $pairCheckLimit = count($pairCandidates);
                }

                foreach ($pairCandidates as $pairCandidate) {
                    if ($checkedPairs >= $pairCheckLimit) {
                        break;
                    }
                    $checkedPairs++;

                    $maleId = (string) $pairCandidate['male_id'];
                    $femaleId = (string) $pairCandidate['female_id'];
                    if ($generation === 1) {
                        $rootFemaleId = $this->extractAnimalIdFromBreederKey($femaleId);
                        $rootMaleId = $this->extractAnimalIdFromBreederKey($maleId);
                        $rootPairKey = ($rootFemaleId ?? 0) . ':' . ($rootMaleId ?? 0);
                        if ($rootFemaleId !== null && $rootMaleId !== null && in_array($rootPairKey, $excludedGenerationOnePairs, true)) {
                            continue;
                        }
                    }

                    $male = $allBreeders[$maleId];
                    $female = $allBreeders[$femaleId];
                        $rows = $this->genotypeCalculator
                            ->setParentsTypeIds($male['type_id'], $female['type_id'])
                            ->getGenotypeFinale(
                                $male['genotype'],
                                $female['genotype'],
                                $dictionary
                            );

                        if (empty($rows)) {
                            continue;
                        }

                        $offspringRows = $this->buildRoadmapOffspringRows($rows, $expectedTraits, $strictVisualOnly);
                        $offspringRows = array_slice($offspringRows, 0, $maxRowsPerStep);
                        $fullTargetRows = collect($rows)
                            ->filter(fn (array $row): bool => $this->rowMatchesExpectedTraits($row, $expectedTraits, $strictVisualOnly))
                            ->values()
                            ->all();
                        $fullTargetProbability = collect($fullTargetRows)->sum(fn (array $row): float => (float) ($row['percentage'] ?? 0));
                        $fullTargetSignature = collect($fullTargetRows)
                            ->sortByDesc(fn (array $row): float => (float) ($row['percentage'] ?? 0))
                            ->map(fn (array $row): string => $this->buildRoadmapRowSignature($row))
                            ->first();

                        $matchedUnion = collect($rows)
                            ->flatMap(fn (array $row): array => $this->extractSupportedTargetsForRow($row, $expectedTraits, $strictVisualOnly))
                            ->unique()
                            ->values()
                            ->all();
                        $bestRowSupport = (int) collect($rows)
                            ->map(fn (array $row): int => count($this->extractSupportedTargetsForRow($row, $expectedTraits, $strictVisualOnly)))
                            ->max();

                        $stepId = ++$stepCounter;
                        $stepRecords[$stepId] = [
                            'id' => $stepId,
                            'generation' => $generation,
                            'male_id' => $maleId,
                            'female_id' => $femaleId,
                            'pairing_label' => $female['label'] . ' x ' . $male['label'],
                            'offspring_rows' => $offspringRows,
                            'matched_union' => $matchedUnion,
                            'full_target_probability' => (float) $fullTargetProbability,
                            'full_target_signature' => $fullTargetSignature ? (string) $fullTargetSignature : null,
                        ];

                        if (!empty($matchedUnion)) {
                            $progressScore = count($matchedUnion);
                            if (
                                $bestProgress === null
                                || $bestRowSupport > (int) ($bestProgress['best_row_support'] ?? 0)
                                || (
                                    $bestRowSupport === (int) ($bestProgress['best_row_support'] ?? 0)
                                    && $progressScore > (int) $bestProgress['matched_count']
                                )
                                || (
                                    $bestRowSupport === (int) ($bestProgress['best_row_support'] ?? 0)
                                    && $progressScore === (int) $bestProgress['matched_count']
                                    && (
                                        (float) $fullTargetProbability > (float) ($bestProgress['probability'] ?? 0)
                                        || (
                                            (float) $fullTargetProbability === (float) ($bestProgress['probability'] ?? 0)
                                            && $generation > (int) ($bestProgress['generation'] ?? 0)
                                        )
                                    )
                                )
                            ) {
                                $bestProgress = [
                                    'step_id' => $stepId,
                                    'matched_count' => $progressScore,
                                    'best_row_support' => $bestRowSupport,
                                    'probability' => (float) $fullTargetProbability,
                                    'generation' => $generation,
                                ];
                            }
                        }

                        if ($fullTargetProbability > 0) {
                            if ($firstGoalGeneration === null) {
                                $firstGoalGeneration = $generation;
                            }
                            if (
                                $bestGoal === null
                                || (
                                    $priorityMode === 'fastest'
                                    && (
                                        $generation < (int) $bestGoal['generation']
                                    )
                                )
                                || (
                                    $priorityMode === 'highest_probability'
                                    && (
                                        $generation === (int) $firstGoalGeneration
                                        && (
                                            (int) ($bestGoal['generation'] ?? 0) !== (int) $firstGoalGeneration
                                            || $fullTargetProbability > (float) $bestGoal['probability']
                                        )
                                    )
                                )
                            ) {
                                $bestGoal = [
                                    'generation' => $generation,
                                    'probability' => (float) $fullTargetProbability,
                                    'step_id' => $stepId,
                                    'target_signature' => (string) ($fullTargetSignature ?? ''),
                                ];
                            }
                        }

                        $keeperCandidates = collect($rows)
                            ->map(function (array $row) use ($expectedTraits, $strictVisualOnly): array {
                                $matched = $this->extractMatchedTargetsForRow($row, $expectedTraits, $strictVisualOnly);
                                $supported = $this->extractSupportedTargetsForRow($row, $expectedTraits, $strictVisualOnly);

                                return [
                                    'row' => $row,
                                    'matched_count' => count(array_unique(array_merge($matched, $supported))),
                                    'matched_targets' => $matched,
                                    'supported_targets' => $supported,
                                    'percentage' => (float) ($row['percentage'] ?? 0),
                                ];
                            })
                            ->filter(fn (array $candidate): bool => $candidate['matched_count'] > 0 && $candidate['percentage'] > 0)
                            ->sort(function (array $a, array $b): int {
                                if ($a['matched_count'] === $b['matched_count']) {
                                    if ($a['percentage'] === $b['percentage']) {
                                        return 0;
                                    }

                                    return $a['percentage'] < $b['percentage'] ? 1 : -1;
                                }

                                return $a['matched_count'] < $b['matched_count'] ? 1 : -1;
                            })
                            ->take($maxKeepersPerPair)
                            ->values()
                            ->all();

                        foreach ($keeperCandidates as $index => $candidate) {
                            $row = $candidate['row'];
                            $virtualGenotype = $this->buildVirtualGenotypeArrayFromRow($row, $categoriesByName);
                            if (empty($virtualGenotype)) {
                                continue;
                            }

                            $newKeepers[] = [
                                'id' => 'v:' . $generation . ':' . $stepId . ':' . $index,
                                'label' => '[G' . $generation . '] ' . $this->formatRoadmapKeeperLabel($row),
                                'type_id' => (int) $typeId,
                                'sex_mode' => 'either',
                                'source_generation' => $generation,
                                'origin_step_id' => $stepId,
                                'origin_row_signature' => $this->buildRoadmapRowSignature($row),
                                'genotype' => $virtualGenotype,
                                'matched_count' => (int) $candidate['matched_count'],
                                'probability' => (float) $candidate['percentage'],
                                'relevance' => (float) $candidate['matched_count'],
                            ];
                        }
                    }
                }

            if (
                ($priorityMode === 'fastest' && $bestGoal !== null && (int) $bestGoal['generation'] === $generation)
                || ($priorityMode === 'highest_probability' && $firstGoalGeneration !== null && (int) $firstGoalGeneration === $generation)
            ) {
                break;
            }

            $selectedKeepers = collect($newKeepers)
                ->sort(function (array $a, array $b): int {
                    $aRelevance = (float) ($a['relevance'] ?? 0);
                    $bRelevance = (float) ($b['relevance'] ?? 0);
                    if ($aRelevance !== $bRelevance) {
                        return $aRelevance < $bRelevance ? 1 : -1;
                    }

                    if ((int) $a['matched_count'] === (int) $b['matched_count']) {
                        if ((float) $a['probability'] === (float) $b['probability']) {
                            return strcmp((string) $a['label'], (string) $b['label']);
                        }

                        return ((float) $a['probability'] < (float) $b['probability']) ? 1 : -1;
                    }

                    return ((int) $a['matched_count'] < (int) $b['matched_count']) ? 1 : -1;
                })
                ->unique(fn (array $row): string => (string) $row['origin_row_signature'] . '#' . (string) $row['type_id'])
                ->take($maxKeepersPerGeneration)
                ->values()
                ->all();

            foreach ($selectedKeepers as $keeper) {
                $keeperId = (string) $keeper['id'];
                $allBreeders[$keeperId] = [
                    'id' => $keeperId,
                    'label' => (string) $keeper['label'],
                    'type_id' => (int) $keeper['type_id'],
                    'sex_mode' => (string) $keeper['sex_mode'],
                    'source_generation' => (int) $keeper['source_generation'],
                    'origin_step_id' => (int) $keeper['origin_step_id'],
                    'origin_row_signature' => (string) $keeper['origin_row_signature'],
                    'genotype' => (array) $keeper['genotype'],
                ];
                $generatedBreederIds[] = $keeperId;
            }
        }

        $finalStepId = $bestGoal['step_id'] ?? ($bestProgress['step_id'] ?? null);
        if ($finalStepId === null || !isset($stepRecords[$finalStepId])) {
            return [
                'target_reachable' => false,
                'matched_traits' => [],
                'missing_traits' => $expectedTraits,
                'steps' => [],
                'root_pair_key' => '',
            ];
        }

        $requiredStepIds = [];
        $usedKeeperSignaturesByStep = [];
        $visitedBreeders = [];
        $traceBreeder = function (string $breederId) use (&$traceBreeder, &$visitedBreeders, &$usedKeeperSignaturesByStep, &$requiredStepIds, $allBreeders, $stepRecords): void {
            if (isset($visitedBreeders[$breederId])) {
                return;
            }
            $visitedBreeders[$breederId] = true;

            $breeder = $allBreeders[$breederId] ?? null;
            if (!$breeder) {
                return;
            }

            $originStepId = (int) ($breeder['origin_step_id'] ?? 0);
            $originSignature = (string) ($breeder['origin_row_signature'] ?? '');
            if ($originStepId <= 0 || !isset($stepRecords[$originStepId])) {
                return;
            }

            $requiredStepIds[$originStepId] = true;
            if ($originSignature !== '') {
                $usedKeeperSignaturesByStep[$originStepId] = $usedKeeperSignaturesByStep[$originStepId] ?? [];
                $usedKeeperSignaturesByStep[$originStepId][$originSignature] = true;
            }

            $originStep = $stepRecords[$originStepId];
            $traceBreeder((string) $originStep['male_id']);
            $traceBreeder((string) $originStep['female_id']);
        };

        $requiredStepIds[$finalStepId] = true;
        $finalStep = $stepRecords[$finalStepId];
        $traceBreeder((string) $finalStep['male_id']);
        $traceBreeder((string) $finalStep['female_id']);

        $orderedStepIds = array_keys($requiredStepIds);
        usort($orderedStepIds, function (int $a, int $b) use ($stepRecords): int {
            $genA = (int) ($stepRecords[$a]['generation'] ?? 0);
            $genB = (int) ($stepRecords[$b]['generation'] ?? 0);
            if ($genA === $genB) {
                return $a <=> $b;
            }

            return $genA <=> $genB;
        });

        $steps = [];
        $cumulativeMatched = [];
        foreach ($orderedStepIds as $stepId) {
            $step = $stepRecords[$stepId];
            $keeperSignatures = array_keys($usedKeeperSignaturesByStep[$stepId] ?? []);
            $targetSignature = $stepId === $finalStepId ? (string) ($bestGoal['target_signature'] ?? '') : '';
            $offspringRows = $this->markRoadmapKeeperRows(
                (array) ($step['offspring_rows'] ?? []),
                $keeperSignatures,
                $targetSignature
            );

            $keeperLabels = collect($offspringRows)
                ->filter(fn (array $row): bool => (bool) ($row['is_keeper'] ?? false))
                ->map(function (array $row): string {
                    $name = trim((string) ($row['traits_name'] ?? ''));
                    if ($name !== '') {
                        return $name;
                    }

                    $vis = collect((array) ($row['visual_traits'] ?? []))->implode(', ');
                    if ($vis !== '') {
                        return $vis;
                    }

                    $carrier = collect((array) ($row['carrier_traits'] ?? []))
                        ->filter(fn (string $label): bool => !str_starts_with(strtolower(trim($label)), '50%'))
                        ->filter(fn (string $label): bool => !str_starts_with(strtolower(trim($label)), '66%'))
                        ->values()
                        ->implode(', ');

                    return $carrier !== '' ? $carrier : '-';
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $matchedTargetsInStep = collect($offspringRows)
                ->filter(fn (array $row): bool => (bool) ($row['is_keeper'] ?? false) || (bool) ($row['is_target'] ?? false))
                ->flatMap(fn (array $row): array => (array) ($row['matched_targets'] ?? []))
                ->unique()
                ->values()
                ->all();

            $cumulativeMatched = collect($cumulativeMatched)
                ->merge($matchedTargetsInStep)
                ->unique()
                ->values()
                ->all();

            $parentMaleId = $this->extractAnimalIdFromBreederKey((string) ($step['male_id'] ?? ''));
            $parentFemaleId = $this->extractAnimalIdFromBreederKey((string) ($step['female_id'] ?? ''));

            $steps[] = [
                'generation' => (int) $step['generation'],
                'pairing_label' => (string) $step['pairing_label'],
                'keeper_label' => !empty($keeperLabels) ? implode(' + ', $keeperLabels) : '-',
                'probability_label' => number_format((float) ($step['full_target_probability'] ?? 0), 2, ',', ' ') . '%',
                'can_create_litter' => $parentMaleId !== null && $parentFemaleId !== null,
                'parent_male_id' => $parentMaleId,
                'parent_female_id' => $parentFemaleId,
                'matched_targets' => $matchedTargetsInStep,
                'matched_count' => count($cumulativeMatched),
                'total_targets' => count($expectedTraits),
                'offspring_rows' => $offspringRows,
            ];
        }

        $shortcut = $this->buildRoadmapSiblingShortcut(
            $breeders,
            $expectedTraits,
            $dictionary,
            $categoriesByName,
            $maxMalesPerType,
            $maxFemalesPerType,
            $maxPairChecksPerType,
            $maxRowsPerStep,
            $strictVisualOnly
        );

        if ($shortcut !== null && $priorityMode === 'fastest') {
            $currentBestGeneration = $bestGoal !== null ? (int) ($bestGoal['generation'] ?? PHP_INT_MAX) : PHP_INT_MAX;
            $shortcutProbability = (float) ($shortcut['best_probability'] ?? 0);
            $currentBestProbability = (float) ($bestGoal['probability'] ?? 0);
            if (
                ($priorityMode === 'fastest' && $currentBestGeneration > 2)
                || (
                    $priorityMode === 'highest_probability'
                    && (
                        $bestGoal === null
                        || $shortcutProbability > $currentBestProbability
                        || (
                            $shortcutProbability === $currentBestProbability
                            && 2 < $currentBestGeneration
                        )
                    )
                )
            ) {
                return $shortcut;
            }
        }

        $targetReachable = $bestGoal !== null;
        $matchedTraits = $targetReachable ? $expectedTraits : $cumulativeMatched;
        $missingTraits = collect($expectedTraits)
            ->reject(fn (string $trait): bool => in_array($trait, $matchedTraits, true))
            ->values()
            ->all();

        $rootPairKey = collect($steps)
            ->first(function (array $step): bool {
                return (int) ($step['generation'] ?? 0) === 1
                    && !empty($step['parent_female_id'])
                    && !empty($step['parent_male_id']);
            });
        $rootPairKey = is_array($rootPairKey)
            ? ((int) ($rootPairKey['parent_female_id'] ?? 0)) . ':' . ((int) ($rootPairKey['parent_male_id'] ?? 0))
            : '';

        $result = [
            'target_reachable' => $targetReachable,
            'matched_traits' => $matchedTraits,
            'missing_traits' => $missingTraits,
            'steps' => $steps,
            'best_goal_probability' => (float) ($bestGoal['probability'] ?? 0),
            'best_goal_generation' => (int) ($bestGoal['generation'] ?? 0),
            'root_pair_key' => $rootPairKey,
        ];

        if ($priorityMode === 'highest_probability' && $allowDiversification && $targetReachable) {
            $excluded = array_values(array_unique(array_filter(array_merge(
                $excludedGenerationOnePairs,
                $rootPairKey !== '' ? [$rootPairKey] : []
            ))));
            $bestResult = $result;

            // 1 base run + max 4 additional runs = max 5 iterations.
            for ($iteration = 2; $iteration <= 5; $iteration++) {
                $candidate = $this->buildRoadmap(
                    $expectedTraits,
                    $maxGenerations,
                    $strictVisualOnly,
                    $priorityMode,
                    $excluded,
                    false
                );

                if (!(bool) ($candidate['target_reachable'] ?? false)) {
                    break;
                }

                $candidateRootPairKey = trim((string) ($candidate['root_pair_key'] ?? ''));
                if ($candidateRootPairKey === '' || in_array($candidateRootPairKey, $excluded, true)) {
                    break;
                }

                $candidateProbability = (float) ($candidate['best_goal_probability'] ?? 0);
                $bestProbability = (float) ($bestResult['best_goal_probability'] ?? 0);
                $candidateGeneration = (int) ($candidate['best_goal_generation'] ?? PHP_INT_MAX);
                $bestGeneration = (int) ($bestResult['best_goal_generation'] ?? PHP_INT_MAX);

                if (
                    $candidateProbability > $bestProbability
                    || (
                        $candidateProbability === $bestProbability
                        && $candidateGeneration < $bestGeneration
                    )
                ) {
                    $bestResult = $candidate;
                }

                $excluded[] = $candidateRootPairKey;
                $excluded = array_values(array_unique($excluded));
            }

            return $bestResult;
        }

        return $result;
    }

    /**
     * @param Collection<int, Animal> $breeders
     * @param array<int, string> $expectedTraits
     * @param array<int, array{0:string|null,1:string|null,2:string|null}> $dictionary
     * @param Collection<string, AnimalGenotypeCategory> $categoriesByName
     * @return array{
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array{
     *         generation:int,
     *         pairing_label:string,
     *         keeper_label:string,
     *         probability_label:string,
     *         can_create_litter:bool,
     *         parent_male_id:int|null,
     *         parent_female_id:int|null,
     *         matched_targets:array<int, string>,
     *         matched_count:int,
     *         total_targets:int,
     *         offspring_rows:array<int, array{
     *             is_keeper:bool,
     *             is_target:bool,
     *             percentage_label:string,
     *             traits_name:string,
     *             visual_traits:array<int, string>,
     *             carrier_traits:array<int, string>,
     *             matched_targets:array<int, string>
     *         }>
     *     }>
     * }|null
     */
    private function buildRoadmapSiblingShortcut(
        Collection $breeders,
        array $expectedTraits,
        array $dictionary,
        Collection $categoriesByName,
        int $maxMalesPerType,
        int $maxFemalesPerType,
        int $maxPairChecksPerType,
        int $maxRowsPerStep,
        bool $strictVisualOnly
    ): ?array {
        if (empty($expectedTraits)) {
            return null;
        }

        $expectedCount = count($expectedTraits);
        $best = null;

        $animalsByType = $breeders
            ->filter(fn (Animal $animal): bool => (int) ($animal->animal_type_id ?? 0) > 0)
            ->groupBy(fn (Animal $animal): int => (int) $animal->animal_type_id);

        foreach ($animalsByType as $typeId => $typeAnimals) {
            $males = $typeAnimals
                ->filter(fn (Animal $animal): bool => (int) $animal->sex === 2)
                ->take($maxMalesPerType)
                ->values();
            $females = $typeAnimals
                ->filter(fn (Animal $animal): bool => (int) $animal->sex === 3)
                ->take($maxFemalesPerType)
                ->values();

            if ($males->isEmpty() || $females->isEmpty()) {
                continue;
            }

            $checkedPairs = 0;
            foreach ($males as $male) {
                foreach ($females as $female) {
                    if ($checkedPairs >= $maxPairChecksPerType) {
                        break;
                    }
                    $checkedPairs++;

                    $rows1 = $this->genotypeCalculator
                        ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
                        ->getGenotypeFinale(
                            $this->buildAnimalGenotypeArray($male),
                            $this->buildAnimalGenotypeArray($female),
                            $dictionary
                        );

                    if (empty($rows1)) {
                        continue;
                    }

                    $keeperCandidates = collect($rows1)
                        ->map(function (array $row) use ($expectedTraits, $strictVisualOnly): array {
                            $supported = $this->extractSupportedTargetsForRow($row, $expectedTraits, $strictVisualOnly);

                            return [
                                'row' => $row,
                                'supported' => $supported,
                                'supported_count' => count($supported),
                                'percentage' => (float) ($row['percentage'] ?? 0),
                                'signature' => $this->buildRoadmapRowSignature($row),
                            ];
                        })
                        ->filter(fn (array $candidate): bool => $candidate['supported_count'] >= count($expectedTraits))
                        ->sort(function (array $a, array $b): int {
                            if ((float) $a['percentage'] === (float) $b['percentage']) {
                                return 0;
                            }

                            return ((float) $a['percentage'] < (float) $b['percentage']) ? 1 : -1;
                        })
                        ->values();

                    if ($keeperCandidates->isEmpty()) {
                        continue;
                    }

                    /** @var array{row:array<string,mixed>,supported:array<int,string>,supported_count:int,percentage:float,signature:string} $keeper */
                    $keeper = $keeperCandidates->first();
                    $virtual = $this->buildVirtualGenotypeArrayFromRow((array) $keeper['row'], $categoriesByName);
                    if (empty($virtual)) {
                        continue;
                    }

                    $rows2 = $this->genotypeCalculator
                        ->setParentsTypeIds((int) $typeId, (int) $typeId)
                        ->getGenotypeFinale($virtual, $virtual, $dictionary);

                    if (empty($rows2)) {
                        continue;
                    }

                    $targetRows2 = collect($rows2)
                        ->filter(fn (array $row): bool => $this->rowMatchesExpectedTraits($row, $expectedTraits, $strictVisualOnly))
                        ->values()
                        ->all();
                    $targetProbability2 = (float) collect($targetRows2)->sum(fn (array $row): float => (float) ($row['percentage'] ?? 0));
                    if ($targetProbability2 <= 0) {
                        continue;
                    }

                    $targetSignature2 = collect($targetRows2)
                        ->sortByDesc(fn (array $row): float => (float) ($row['percentage'] ?? 0))
                        ->map(fn (array $row): string => $this->buildRoadmapRowSignature($row))
                        ->first();

                    $candidatePlan = [
                        'g1_pairing' => $this->normalizeName($female->name) . ' x ' . $this->normalizeName($male->name),
                        'g1_parent_male_id' => (int) $male->id,
                        'g1_parent_female_id' => (int) $female->id,
                        'g1_rows' => $this->markRoadmapKeeperRows(
                            array_slice($this->buildRoadmapOffspringRows($rows1, $expectedTraits, $strictVisualOnly), 0, $maxRowsPerStep),
                            [(string) $keeper['signature']]
                        ),
                        'g1_keeper_label' => $this->formatRoadmapKeeperLabel((array) $keeper['row']),
                        'g1_probability' => (float) $keeper['percentage'],
                        'g1_matched' => $this->extractSupportedTargetsForRow((array) $keeper['row'], $expectedTraits, $strictVisualOnly),
                        'g2_pairing' => '[G1] ' . $this->formatRoadmapKeeperLabel((array) $keeper['row']) . ' x [G1] ' . $this->formatRoadmapKeeperLabel((array) $keeper['row']),
                        'g2_rows' => $this->markRoadmapKeeperRows(
                            array_slice($this->buildRoadmapOffspringRows($rows2, $expectedTraits, $strictVisualOnly), 0, $maxRowsPerStep),
                            [],
                            (string) ($targetSignature2 ?? '')
                        ),
                        'g2_probability' => $targetProbability2,
                    ];

                    if (
                        $best === null
                        || (float) $candidatePlan['g2_probability'] > (float) $best['g2_probability']
                        || (
                            (float) $candidatePlan['g2_probability'] === (float) $best['g2_probability']
                            && (float) $candidatePlan['g1_probability'] > (float) $best['g1_probability']
                        )
                    ) {
                        $best = $candidatePlan;
                    }
                }
            }
        }

        if ($best === null) {
            return null;
        }

        $g1ParentMaleId = (int) ($best['g1_parent_male_id'] ?? 0);
        $g1ParentFemaleId = (int) ($best['g1_parent_female_id'] ?? 0);

        $steps = [
            [
                'generation' => 1,
                'pairing_label' => (string) $best['g1_pairing'],
                'keeper_label' => (string) $best['g1_keeper_label'],
                'probability_label' => number_format((float) $best['g1_probability'], 2, ',', ' ') . '%',
                'can_create_litter' => $g1ParentMaleId > 0 && $g1ParentFemaleId > 0,
                'parent_male_id' => $g1ParentMaleId > 0 ? $g1ParentMaleId : null,
                'parent_female_id' => $g1ParentFemaleId > 0 ? $g1ParentFemaleId : null,
                'matched_targets' => (array) $best['g1_matched'],
                'matched_count' => count((array) $best['g1_matched']),
                'total_targets' => $expectedCount,
                'offspring_rows' => (array) $best['g1_rows'],
            ],
            [
                'generation' => 2,
                'pairing_label' => (string) $best['g2_pairing'],
                'keeper_label' => 'Docelowe potomstwo',
                'probability_label' => number_format((float) $best['g2_probability'], 2, ',', ' ') . '%',
                'can_create_litter' => false,
                'parent_male_id' => null,
                'parent_female_id' => null,
                'matched_targets' => $expectedTraits,
                'matched_count' => $expectedCount,
                'total_targets' => $expectedCount,
                'offspring_rows' => (array) $best['g2_rows'],
            ],
        ];

        return [
            'target_reachable' => true,
            'matched_traits' => $expectedTraits,
            'missing_traits' => [],
            'best_probability' => (float) ($best['g2_probability'] ?? 0),
            'root_pair_key' => $g1ParentFemaleId > 0 && $g1ParentMaleId > 0
                ? ($g1ParentFemaleId . ':' . $g1ParentMaleId)
                : '',
            'steps' => $steps,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $expectedTraits
     */
    private function rowMatchesExpectedTraits(array $row, array $expectedTraits, bool $strictVisualOnly): bool
    {
        $normalizedVisualTraits = collect((array) ($row['visual_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->normalizeTrait((string) $trait))
            ->filter()
            ->values()
            ->all();

        $normalizedCarrierTraits = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->normalizeCarrierLabelForMatching((string) $trait))
            ->filter()
            ->values()
            ->all();
        $resolvedCarrierGenes = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->parseCarrierGeneName((string) $trait))
            ->filter()
            ->values()
            ->all();

        $normalizedTraitsName = $this->normalizeTrait((string) ($row['traits_name'] ?? ''));
        $expectedVisualTraits = collect($expectedTraits)
            ->map(fn (string $trait): string => $this->normalizeTrait($trait))
            ->filter()
            ->reject(fn (string $trait): bool => str_starts_with($trait, 'het '))
            ->values()
            ->all();

        foreach ($expectedTraits as $trait) {
            $expected = $this->normalizeTrait($trait);
            if ($expected === '') {
                return false;
            }

            if (str_starts_with($expected, 'het ')) {
                $expectedHetGene = $this->normalizeTrait((string) preg_replace('/^het\s+/i', '', $expected));
                $isAmbiguousExpected = str_contains($expectedHetGene, '/');

                $isMatched = $isAmbiguousExpected
                    ? collect($normalizedCarrierTraits)
                        ->contains(fn (string $carrier): bool => $carrier === $expected)
                    : collect($resolvedCarrierGenes)
                        ->contains(fn (string $carrierGene): bool => $this->matchesWholeTrait($carrierGene, $expectedHetGene));

                if (!$isMatched) {
                    return false;
                }

                continue;
            }

            $isMatched = collect($normalizedVisualTraits)
                ->contains(fn (string $visual): bool => $this->matchesWholeTrait($visual, $expected))
                || $this->matchesWholeTrait($normalizedTraitsName, $expected);

            if (!$isMatched) {
                return false;
            }
        }

        if ($strictVisualOnly) {
            foreach ($normalizedVisualTraits as $visualTrait) {
                $isExpectedVisual = collect($expectedVisualTraits)
                    ->contains(function (string $expectedVisual) use ($visualTrait): bool {
                        return $this->matchesWholeTrait($visualTrait, $expectedVisual);
                    });

                if (!$isExpectedVisual) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     percentage:float,
     *     percentage_label:string,
     *     traits_name:string,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>
     * }
     */
    private function mapMatchedRow(array $row): array
    {
        $percentage = (float) ($row['percentage'] ?? 0);

        return [
            'percentage' => $percentage,
            'percentage_label' => number_format($percentage, 2, ',', ' ') . '%',
            'traits_name' => trim((string) ($row['traits_name'] ?? '')),
            'visual_traits' => collect((array) ($row['visual_traits'] ?? []))
                ->map(fn (mixed $trait): string => trim((string) $trait))
                ->filter()
                ->values()
                ->all(),
            'carrier_traits' => $this->sortCarrierTraitsForDisplay(collect((array) ($row['carrier_traits'] ?? []))
                ->map(fn (mixed $trait): string => trim((string) $trait))
                ->filter()
                ->values()
                ->all()),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param \Illuminate\Support\Collection<string, AnimalGenotypeCategory> $categoriesByName
     * @return array<int, array{0:string,1:string}>
     */
    private function buildVirtualGenotypeArrayFromRow(array $row, Collection $categoriesByName): array
    {
        $result = [];

        $visualTraits = collect((array) ($row['visual_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->normalizeTrait((string) $trait))
            ->filter()
            ->values();

        foreach ($visualTraits as $visualTrait) {
            $category = $categoriesByName->get($visualTrait);
            if (!$category) {
                continue;
            }

            $geneCode = (string) ($category->gene_code ?? '');
            $geneType = strtolower((string) ($category->gene_type ?? ''));
            if ($geneCode === '') {
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

        $carrierTraits = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->parseCarrierGeneName((string) $trait))
            ->filter()
            ->values();

        foreach ($carrierTraits as $carrierGeneName) {
            $category = $categoriesByName->get($carrierGeneName);
            if (!$category) {
                continue;
            }

            $geneCode = (string) ($category->gene_code ?? '');
            if ($geneCode === '') {
                continue;
            }

            $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
        }

        return collect($result)
            ->unique(fn (array $pair): string => implode(':', $pair))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $expectedTraits
     * @return array<int, string>
     */
    private function extractMatchedTargetsForRow(array $row, array $expectedTraits, bool $strictVisualOnly): array
    {
        $matches = [];
        foreach ($expectedTraits as $targetTrait) {
            if ($this->rowMatchesExpectedTraits($row, [$targetTrait], $strictVisualOnly)) {
                $matches[] = $targetTrait;
            }
        }

        return collect($matches)->unique()->values()->all();
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $expectedTraits
     * @return array<int, string>
     */
    private function extractSupportedTargetsForRow(array $row, array $expectedTraits, bool $strictVisualOnly): array
    {
        $supported = [];

        $normalizedVisual = collect((array) ($row['visual_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->normalizeTrait((string) $trait))
            ->filter()
            ->values()
            ->all();

        $normalizedCarrier = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->parseCarrierGeneName((string) $trait))
            ->filter()
            ->values()
            ->all();

        foreach ($expectedTraits as $expectedTraitRaw) {
            $expectedTrait = $this->normalizeTrait((string) $expectedTraitRaw);
            if ($expectedTrait === '') {
                continue;
            }

            if ($this->rowMatchesExpectedTraits($row, [$expectedTrait], $strictVisualOnly)) {
                $supported[] = $expectedTrait;
                continue;
            }

            $expectedGene = preg_replace('/^het\s+/i', '', $expectedTrait) ?? $expectedTrait;
            $expectedGene = $this->normalizeTrait($expectedGene);
            if ($expectedGene === '') {
                continue;
            }

            $hasVisualOrCarrierSupport = collect($normalizedVisual)
                ->contains(fn (string $trait): bool => $this->matchesWholeTrait($trait, $expectedGene))
                || collect($normalizedCarrier)
                    ->contains(fn (string $trait): bool => $this->matchesWholeTrait($trait, $expectedGene));

            if ($hasVisualOrCarrierSupport) {
                $supported[] = $expectedTrait;
            }
        }

        return collect($supported)->unique()->values()->all();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatRoadmapKeeperLabel(array $row): string
    {
        $name = trim((string) ($row['traits_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $visual = collect((array) ($row['visual_traits'] ?? []))
            ->map(fn (mixed $trait): string => trim((string) $trait))
            ->filter()
            ->values()
            ->all();

        $carrier = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => trim((string) $trait))
            ->filter()
            ->values()
            ->all();
        $carrier = $this->sortCarrierTraitsForDisplay($carrier);

        $parts = array_merge($visual, $carrier);
        if (empty($parts)) {
            return 'Nieokreslony keeper';
        }

        return implode(', ', array_slice($parts, 0, 4));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $expectedTraits
     * @return array<int, array{
     *     is_keeper:bool,
     *     signature:string,
     *     percentage:float,
     *     percentage_label:string,
     *     traits_name:string,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>,
     *     matched_targets:array<int, string>
     * }>
     */
    private function buildRoadmapOffspringRows(array $rows, array $expectedTraits, bool $strictVisualOnly): array
    {
        return collect($rows)
            ->map(function (array $row) use ($expectedTraits, $strictVisualOnly): array {
                $percentage = (float) ($row['percentage'] ?? 0);

                return [
                    'is_keeper' => false,
                    'signature' => $this->buildRoadmapRowSignature($row),
                    'percentage' => $percentage,
                    'percentage_label' => number_format($percentage, 2, ',', ' ') . '%',
                    'traits_name' => trim((string) ($row['traits_name'] ?? '')),
                    'visual_traits' => collect((array) ($row['visual_traits'] ?? []))
                        ->map(fn (mixed $trait): string => trim((string) $trait))
                        ->filter()
                        ->values()
                        ->all(),
                    'carrier_traits' => $this->sortCarrierTraitsForDisplay(collect((array) ($row['carrier_traits'] ?? []))
                        ->map(fn (mixed $trait): string => trim((string) $trait))
                        ->filter()
                        ->values()
                        ->all()),
                    'matched_targets' => $this->extractMatchedTargetsForRow($row, $expectedTraits, $strictVisualOnly),
                ];
            })
            ->sortByDesc('percentage')
            ->values()
            ->all();
    }

    private function buildRoadmapRowSignature(array $row): string
    {
        $visual = collect((array) ($row['visual_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->normalizeTrait((string) $trait))
            ->filter()
            ->sort()
            ->values()
            ->implode('|');
        $carrier = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => $this->normalizeTrait((string) $trait))
            ->filter()
            ->sort()
            ->values()
            ->implode('|');
        $name = $this->normalizeTrait((string) ($row['traits_name'] ?? ''));
        $percentage = number_format((float) ($row['percentage'] ?? 0), 4, '.', '');

        return $name . '#' . $visual . '#' . $carrier . '#' . $percentage;
    }

    /**
     * @param array<int, array{
     *     is_keeper:bool,
     *     signature:string,
     *     percentage:float,
     *     percentage_label:string,
     *     traits_name:string,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>,
     *     matched_targets:array<int, string>
     * }> $rows
     * @param array<int, string> $keeperSignatures
     * @return array<int, array{
     *     is_keeper:bool,
     *     is_target:bool,
     *     percentage_label:string,
     *     traits_name:string,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>,
     *     matched_targets:array<int, string>
     * }>
     */
    private function markRoadmapKeeperRows(array $rows, array $keeperSignatures, string $targetSignature = ''): array
    {
        $keeperMap = collect($keeperSignatures)
            ->filter()
            ->mapWithKeys(fn (string $signature): array => [$signature => true])
            ->all();

        return collect($rows)
            ->map(function (array $row) use ($keeperMap, $targetSignature): array {
                $signature = (string) ($row['signature'] ?? '');
                $isKeeper = $signature !== '' && isset($keeperMap[$signature]);
                $isTarget = $targetSignature !== '' && $signature === $targetSignature;

                return [
                    'is_keeper' => $isKeeper,
                    'is_target' => $isTarget,
                    'percentage_label' => (string) ($row['percentage_label'] ?? '0,00%'),
                    'traits_name' => (string) ($row['traits_name'] ?? ''),
                    'visual_traits' => array_values((array) ($row['visual_traits'] ?? [])),
                    'carrier_traits' => array_values((array) ($row['carrier_traits'] ?? [])),
                    'matched_targets' => array_values((array) ($row['matched_targets'] ?? [])),
                ];
            })
            ->values()
            ->pipe(function (Collection $rows): array {
                $hasKeeper = $rows->contains(fn (array $row): bool => (bool) ($row['is_keeper'] ?? false));
                if ($hasKeeper) {
                    return $rows->all();
                }

                $rowsArray = $rows->all();
                $candidateIndex = $rows
                    ->search(fn (array $row): bool => !empty($row['matched_targets']));

                if ($candidateIndex === false) {
                    $candidateIndex = !empty($rowsArray) ? 0 : false;
                }

                if ($candidateIndex !== false) {
                    $rowsArray[$candidateIndex]['is_keeper'] = true;
                }

                return $rowsArray;
            });
    }

    private function parseCarrierGeneName(string $label): string
    {
        $normalized = $this->normalizeTrait($label);
        if ($normalized === '') {
            return '';
        }

        if ($this->isProbabilisticCarrierLabel($normalized)) {
            return '';
        }

        if (preg_match('/(?:[\d.,]+%\s+)?het\s+(.+)$/i', $normalized, $matches) !== 1) {
            return '';
        }

        $gene = $this->normalizeTrait((string) ($matches[1] ?? ''));
        if ($gene === '') {
            return '';
        }

        // "het amel/ultra" or similar is ambiguous and must not be treated as
        // both "het amel" and "het ultra".
        if (str_contains($gene, '/')) {
            return '';
        }

        return $gene;
    }

    private function normalizeCarrierLabelForMatching(string $label): string
    {
        $normalized = $this->normalizeTrait($label);
        if ($normalized === '') {
            return '';
        }

        if ($this->isProbabilisticCarrierLabel($normalized)) {
            return '';
        }

        return $this->normalizeTrait((string) preg_replace('/^[\d.,]+%\s+/', '', $normalized));
    }

    /**
     * @param array<int, string> $traits
     * @return array<int, string>
     */
    private function sortCarrierTraitsForDisplay(array $traits): array
    {
        return collect($traits)
            ->map(fn (string $trait): string => trim($trait))
            ->filter()
            ->values()
            ->map(function (string $trait, int $index): array {
                return [
                    'trait' => $trait,
                    'index' => $index,
                    'group' => $this->carrierDisplayGroup($trait),
                ];
            })
            ->sort(function (array $a, array $b): int {
                if ((int) $a['group'] === (int) $b['group']) {
                    return (int) $a['index'] <=> (int) $b['index'];
                }

                return (int) $a['group'] <=> (int) $b['group'];
            })
            ->pluck('trait')
            ->values()
            ->all();
    }

    private function carrierDisplayGroup(string $trait): int
    {
        $normalized = $this->normalizeTrait($trait);
        if ($normalized === '') {
            return 3;
        }

        if (preg_match('/^([\d.,]+)%\s+het\s+/i', $normalized, $matches) === 1) {
            $percentRaw = str_replace(',', '.', (string) ($matches[1] ?? ''));
            if (is_numeric($percentRaw) && (float) $percentRaw < 100.0) {
                // p (possible) at the end
                return 2;
            }
        }

        if (str_contains($normalized, 'het ')) {
            // r/h (heterozygous) before possible
            return 1;
        }

        return 0;
    }

    private function isProbabilisticCarrierLabel(string $normalizedLabel): bool
    {
        if (preg_match('/^([\d.,]+)%\s+het\s+/i', $normalizedLabel, $matches) !== 1) {
            return false;
        }

        $percent = str_replace(',', '.', (string) ($matches[1] ?? ''));
        if (!is_numeric($percent)) {
            return false;
        }

        return (float) $percent < 100.0;
    }

    private function extractAnimalIdFromBreederKey(string $breederKey): ?int
    {
        $normalized = trim($breederKey);
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, 'a:')) {
            $candidate = (int) substr($normalized, 2);
            return $candidate > 0 ? $candidate : null;
        }

        $candidate = (int) $normalized;

        return $candidate > 0 ? $candidate : null;
    }

    private function normalizeTrait(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @return array<int, string>
     */
    private function parseRootPairKeys(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => preg_match('/^\d+:\d+$/', $part) === 1)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     */
    private function extractRootPairKeyFromSteps(array $steps): string
    {
        $step = collect($steps)
            ->first(function (array $row): bool {
                return (int) ($row['generation'] ?? 0) === 1
                    && !empty($row['parent_female_id'])
                    && !empty($row['parent_male_id']);
            });

        if (!is_array($step)) {
            return '';
        }

        $femaleId = (int) ($step['parent_female_id'] ?? 0);
        $maleId = (int) ($step['parent_male_id'] ?? 0);
        if ($femaleId <= 0 || $maleId <= 0) {
            return '';
        }

        return $femaleId . ':' . $maleId;
    }

    private function matchesWholeTrait(string $haystack, string $needle): bool
    {
        $normalizedHaystack = $this->normalizeTrait($haystack);
        $normalizedNeedle = $this->normalizeTrait($needle);

        if ($normalizedHaystack === '' || $normalizedNeedle === '') {
            return false;
        }

        if ($normalizedHaystack === $normalizedNeedle) {
            return true;
        }

        $pattern = '/\b' . preg_quote($normalizedNeedle, '/') . '\b/i';

        return preg_match($pattern, $normalizedHaystack) === 1;
    }
}
