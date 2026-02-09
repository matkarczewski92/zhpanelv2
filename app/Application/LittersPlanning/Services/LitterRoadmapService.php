<?php

namespace App\Application\LittersPlanning\Services;

use App\Models\Animal;
use App\Models\LitterRoadmap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LitterRoadmapService
{
    /**
     * @param array{
     *     name:string,
     *     search_input:string,
     *     generations:int,
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array<string, mixed>>
     * } $payload
     */
    public function store(array $payload): LitterRoadmap
    {
        return DB::transaction(function () use ($payload): LitterRoadmap {
            return LitterRoadmap::query()->create($this->mapPayload($payload, []));
        });
    }

    /**
     * @param array{
     *     name:string,
     *     search_input:string,
     *     generations:int,
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array<string, mixed>>
     * } $payload
     */
    public function update(LitterRoadmap $roadmap, array $payload): LitterRoadmap
    {
        return DB::transaction(function () use ($roadmap, $payload): LitterRoadmap {
            $existingCompleted = $this->normalizeCompletedGenerations(
                (array) ($roadmap->completed_generations ?? []),
                (array) ($payload['steps'] ?? [])
            );

            $roadmap->fill($this->mapPayload($payload, $existingCompleted));
            $roadmap->save();

            return $roadmap->refresh();
        });
    }

    /**
     * @param array{
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array<string, mixed>>
     * } $snapshot
     */
    public function refresh(LitterRoadmap $roadmap, array $snapshot): LitterRoadmap
    {
        return DB::transaction(function () use ($roadmap, $snapshot): LitterRoadmap {
            $existingSteps = array_values((array) ($roadmap->steps ?? []));
            $completedGenerations = $this->normalizeCompletedGenerations(
                (array) ($roadmap->completed_generations ?? []),
                $existingSteps
            );

            $stepsToPersist = $existingSteps;
            if ($stepsToPersist === []) {
                $stepsToPersist = array_values((array) ($snapshot['steps'] ?? []));
                $roadmap->expected_traits = array_values((array) ($snapshot['expected_traits'] ?? []));
                $roadmap->target_reachable = (bool) ($snapshot['target_reachable'] ?? false);
                $roadmap->matched_traits = array_values((array) ($snapshot['matched_traits'] ?? []));
                $roadmap->missing_traits = array_values((array) ($snapshot['missing_traits'] ?? []));
            } else {
                $stepsToPersist = $this->resolveVirtualBreedersInSteps($stepsToPersist);
            }

            $roadmap->steps = $this->applyRealizedFlags($stepsToPersist, $completedGenerations);
            $roadmap->completed_generations = $this->normalizeCompletedGenerations($completedGenerations, $stepsToPersist);
            $roadmap->last_refreshed_at = now();
            $roadmap->save();

            return $roadmap->refresh();
        });
    }

    public function setGenerationRealized(LitterRoadmap $roadmap, int $generation, bool $realized): LitterRoadmap
    {
        return DB::transaction(function () use ($roadmap, $generation, $realized): LitterRoadmap {
            $steps = array_values((array) ($roadmap->steps ?? []));
            $availableGenerations = collect($steps)
                ->filter(fn (mixed $step): bool => is_array($step))
                ->map(fn (array $step): int => (int) ($step['generation'] ?? 0))
                ->filter(fn (int $gen): bool => $gen > 0)
                ->unique()
                ->values()
                ->all();

            if (!in_array($generation, $availableGenerations, true)) {
                return $roadmap->refresh();
            }

            $completed = array_values((array) ($roadmap->completed_generations ?? []));
            if ($realized) {
                $completed[] = $generation;
            } else {
                $completed = array_values(array_filter(
                    $completed,
                    fn (mixed $gen): bool => (int) $gen !== $generation
                ));
            }

            $completed = $this->normalizeCompletedGenerations($completed, $steps);
            $roadmap->completed_generations = $completed;
            $roadmap->steps = $this->applyRealizedFlags($steps, $completed);
            $roadmap->save();

            return $roadmap->refresh();
        });
    }

    public function rename(LitterRoadmap $roadmap, string $name): LitterRoadmap
    {
        return DB::transaction(function () use ($roadmap, $name): LitterRoadmap {
            $roadmap->name = trim($name);
            $roadmap->save();

            return $roadmap->refresh();
        });
    }

    public function delete(LitterRoadmap $roadmap): void
    {
        DB::transaction(static function () use ($roadmap): void {
            $roadmap->delete();
        });
    }

    /**
     * @param array{
     *     name:string,
     *     search_input:string,
     *     generations:int,
     *     expected_traits:array<int, string>,
     *     target_reachable:bool,
     *     matched_traits:array<int, string>,
     *     missing_traits:array<int, string>,
     *     steps:array<int, array<string, mixed>>
     * } $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload, array $completedGenerations): array
    {
        $generations = (int) ($payload['generations'] ?? 0);
        $steps = array_values((array) ($payload['steps'] ?? []));
        $completed = $this->normalizeCompletedGenerations($completedGenerations, $steps);

        return [
            'name' => trim((string) ($payload['name'] ?? '')),
            'search_input' => trim((string) ($payload['search_input'] ?? '')),
            'generations' => $generations > 0 ? $generations : null,
            'expected_traits' => array_values((array) ($payload['expected_traits'] ?? [])),
            'target_reachable' => (bool) ($payload['target_reachable'] ?? false),
            'matched_traits' => array_values((array) ($payload['matched_traits'] ?? [])),
            'missing_traits' => array_values((array) ($payload['missing_traits'] ?? [])),
            'steps' => $this->applyRealizedFlags($steps, $completed),
            'completed_generations' => $completed,
            'last_refreshed_at' => now(),
        ];
    }

    /**
     * @param array<int, mixed> $completedGenerations
     * @param array<int, mixed> $steps
     * @return array<int, int>
     */
    private function normalizeCompletedGenerations(array $completedGenerations, array $steps): array
    {
        $availableGenerations = collect($steps)
            ->filter(fn (mixed $step): bool => is_array($step))
            ->map(fn (array $step): int => (int) ($step['generation'] ?? 0))
            ->filter(fn (int $generation): bool => $generation > 0)
            ->unique()
            ->values()
            ->all();

        return collect($completedGenerations)
            ->map(fn (mixed $generation): int => (int) $generation)
            ->filter(fn (int $generation): bool => $generation > 0)
            ->filter(fn (int $generation): bool => in_array($generation, $availableGenerations, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $steps
     * @param array<int, int> $completedGenerations
     * @return array<int, mixed>
     */
    private function applyRealizedFlags(array $steps, array $completedGenerations): array
    {
        return collect($steps)
            ->map(function (mixed $step) use ($completedGenerations): mixed {
                if (!is_array($step)) {
                    return $step;
                }

                $generation = (int) ($step['generation'] ?? 0);
                $step['is_realized'] = $generation > 0 && in_array($generation, $completedGenerations, true);

                return $step;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $steps
     * @return array<int, mixed>
     */
    private function resolveVirtualBreedersInSteps(array $steps): array
    {
        $breeders = Animal::query()
            ->whereIn('animal_category_id', [1, 4])
            ->whereIn('sex', [2, 3])
            ->with(['genotypes.category'])
            ->orderBy('id')
            ->get(['id', 'name', 'second_name', 'sex']);

        if ($breeders->isEmpty()) {
            return $steps;
        }

        $candidatesBySex = [
            2 => [],
            3 => [],
        ];

        foreach ($breeders as $animal) {
            $sex = (int) ($animal->sex ?? 0);
            if (!isset($candidatesBySex[$sex])) {
                continue;
            }

            $searchLabels = collect([
                $this->normalizeDescriptor((string) ($animal->name ?? '')),
                $this->normalizeDescriptor((string) ($animal->second_name ?? '')),
                $this->normalizeDescriptor(trim((string) ($animal->name ?? '') . ' ' . (string) ($animal->second_name ?? ''))),
            ])
                ->merge($this->buildMorphSearchLabels($animal))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($searchLabels === []) {
                continue;
            }

            $candidatesBySex[$sex][] = [
                'id' => (int) $animal->id,
                'name' => trim((string) ($animal->name ?? '')) !== '' ? trim((string) ($animal->name ?? '')) : ('#' . (int) $animal->id),
                'search_labels' => $searchLabels,
            ];
        }

        return collect($steps)
            ->map(function (mixed $step) use ($candidatesBySex): mixed {
                if (!is_array($step)) {
                    return $step;
                }

                $pairingLabel = trim((string) ($step['pairing_label'] ?? ''));
                if ($pairingLabel === '') {
                    return $step;
                }

                [$femalePart, $malePart] = $this->splitPairingLabel($pairingLabel);
                if ($malePart === '') {
                    return $step;
                }

                $parentFemaleId = isset($step['parent_female_id']) ? (int) ($step['parent_female_id'] ?? 0) : 0;
                $parentMaleId = isset($step['parent_male_id']) ? (int) ($step['parent_male_id'] ?? 0) : 0;

                if ($parentFemaleId <= 0) {
                    $virtualFemaleLabel = $this->extractVirtualLabel($femalePart);
                    if ($virtualFemaleLabel !== null) {
                        $resolvedFemale = $this->resolveBreederIdByLabel($virtualFemaleLabel, (array) ($candidatesBySex[3] ?? []));
                        if ($resolvedFemale !== null) {
                            $parentFemaleId = (int) ($resolvedFemale['id'] ?? 0);
                            $femalePart = (string) ($resolvedFemale['name'] ?? $femalePart);
                        }
                    }
                }

                if ($parentMaleId <= 0) {
                    $virtualMaleLabel = $this->extractVirtualLabel($malePart);
                    if ($virtualMaleLabel !== null) {
                        $resolvedMale = $this->resolveBreederIdByLabel($virtualMaleLabel, (array) ($candidatesBySex[2] ?? []));
                        if ($resolvedMale !== null) {
                            $parentMaleId = (int) ($resolvedMale['id'] ?? 0);
                            $malePart = (string) ($resolvedMale['name'] ?? $malePart);
                        }
                    }
                }

                $step['pairing_label'] = trim($femalePart) . ' x ' . trim($malePart);
                $step['parent_female_id'] = $parentFemaleId > 0 ? $parentFemaleId : null;
                $step['parent_male_id'] = $parentMaleId > 0 ? $parentMaleId : null;
                $step['can_create_litter'] = $parentFemaleId > 0 && $parentMaleId > 0;

                return $step;
            })
            ->values()
            ->all();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitPairingLabel(string $pairingLabel): array
    {
        $parts = preg_split('/\s+x\s+/iu', trim($pairingLabel), 2) ?: [];
        if (count($parts) < 2) {
            return [trim($pairingLabel), ''];
        }

        return [trim((string) ($parts[0] ?? '')), trim((string) ($parts[1] ?? ''))];
    }

    private function extractVirtualLabel(string $side): ?string
    {
        $trimmed = trim($side);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\[g\d+\]\s*(.+)$/iu', $trimmed, $matches) !== 1) {
            return null;
        }

        $label = trim((string) ($matches[1] ?? ''));

        return $label !== '' ? $label : null;
    }

    /**
     * @param array<int, array{id:int,name:string,search_labels:array<int,string>}> $candidates
     * @return array{id:int,name:string,search_labels:array<int,string>}|null
     */
    private function resolveBreederIdByLabel(string $virtualLabel, array $candidates): ?array
    {
        $needle = $this->normalizeDescriptor($virtualLabel);
        if ($needle === '' || $candidates === []) {
            return null;
        }

        $exactMatches = array_values(array_filter(
            $candidates,
            fn (array $candidate): bool => in_array($needle, (array) ($candidate['search_labels'] ?? []), true)
        ));
        if (count($exactMatches) === 1) {
            return $exactMatches[0];
        }

        $containsMatches = array_values(array_filter($candidates, function (array $candidate) use ($needle): bool {
            foreach ((array) ($candidate['search_labels'] ?? []) as $label) {
                if ($label === '') {
                    continue;
                }

                if (str_contains((string) $label, $needle) || str_contains($needle, (string) $label)) {
                    return true;
                }
            }

            return false;
        }));

        return count($containsMatches) === 1 ? $containsMatches[0] : null;
    }

    /**
     * @return array<int, string>
     */
    private function buildMorphSearchLabels(Animal $animal): array
    {
        $visualTraits = [];
        $carrierTraits = [];

        foreach ($animal->genotypes as $genotype) {
            $category = $genotype->category;
            $categoryName = trim((string) ($category?->name ?? ''));
            if ($categoryName === '') {
                continue;
            }

            $type = strtolower((string) ($genotype->type ?? ''));
            if ($type === 'v') {
                $visualTraits[] = $categoryName;
                continue;
            }

            if ($type === 'h') {
                $carrierTraits[] = '100% het ' . $categoryName;
                continue;
            }

            if ($type === 'p') {
                $carrierTraits[] = '66% het ' . $categoryName;
            }
        }

        $visualTraits = array_values(array_unique($visualTraits));
        $carrierTraits = array_values(array_unique($carrierTraits));

        $labels = [];
        if ($visualTraits !== []) {
            $visualLabel = implode(' ', $visualTraits);
            $labels[] = $visualLabel;
            if ($carrierTraits !== []) {
                $labels[] = $visualLabel . ' (' . implode(', ', $carrierTraits) . ')';
            }
        }

        if ($carrierTraits !== []) {
            $labels[] = implode(', ', $carrierTraits);
        }

        return collect($labels)
            ->map(fn (string $label): string => $this->normalizeDescriptor($label))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeDescriptor(string $value): string
    {
        $ascii = Str::ascii(strtolower(trim($value)));
        $ascii = preg_replace('/[^a-z0-9%\/]+/', ' ', $ascii) ?? $ascii;
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?? $ascii;

        return trim($ascii);
    }
}
