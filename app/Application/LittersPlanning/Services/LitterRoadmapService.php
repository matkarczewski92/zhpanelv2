<?php

namespace App\Application\LittersPlanning\Services;

use App\Models\LitterRoadmap;
use Illuminate\Support\Facades\DB;

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
            $newSteps = array_values((array) ($snapshot['steps'] ?? []));
            $completedGenerations = $this->normalizeCompletedGenerations(
                (array) ($roadmap->completed_generations ?? []),
                $existingSteps
            );

            $preservedByGeneration = collect($existingSteps)
                ->filter(fn (mixed $step): bool => is_array($step))
                ->mapWithKeys(function (array $step): array {
                    $generation = (int) ($step['generation'] ?? 0);
                    if ($generation <= 0) {
                        return [];
                    }

                    return [$generation => $step];
                })
                ->all();

            $mergedSteps = collect($newSteps)
                ->map(function (mixed $step) use ($completedGenerations, $preservedByGeneration): mixed {
                    if (!is_array($step)) {
                        return $step;
                    }

                    $generation = (int) ($step['generation'] ?? 0);
                    if ($generation <= 0) {
                        return $step;
                    }

                    if (in_array($generation, $completedGenerations, true) && isset($preservedByGeneration[$generation])) {
                        return $preservedByGeneration[$generation];
                    }

                    return $step;
                })
                ->values()
                ->all();

            $roadmap->expected_traits = $snapshot['expected_traits'];
            $roadmap->target_reachable = $snapshot['target_reachable'];
            $roadmap->matched_traits = $snapshot['matched_traits'];
            $roadmap->missing_traits = $snapshot['missing_traits'];
            $roadmap->steps = $this->applyRealizedFlags($mergedSteps, $completedGenerations);
            $roadmap->completed_generations = $this->normalizeCompletedGenerations($completedGenerations, $mergedSteps);
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
}
