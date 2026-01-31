<?php

namespace App\Services\Animal;

use App\Application\Animals\ViewModels\AnimalWeightChartViewModel;
use App\Models\AnimalFeeding;
use App\Models\AnimalWeight;
use Illuminate\Support\Collection;

class AnimalWeightChartService
{
    private const FEED_INDEX_STEP = 50;

    public function buildForAnimal(int $animalId): AnimalWeightChartViewModel
    {
        $weights = $this->fetchWeights($animalId);
        $feedings = $this->fetchFeedings($animalId);

        return $this->buildFromCollections($weights, $feedings);
    }

    /**
     * @return Collection<int, AnimalWeight>
     */
    public function fetchWeights(int $animalId): Collection
    {
        return AnimalWeight::query()
            ->where('animal_id', $animalId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return Collection<int, AnimalFeeding>
     */
    public function fetchFeedings(int $animalId): Collection
    {
        return AnimalFeeding::query()
            ->with('feed')
            ->where('animal_id', $animalId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param Collection<int, AnimalWeight> $weights
     * @param Collection<int, AnimalFeeding> $feedings
     */
    public function buildFromCollections(Collection $weights, Collection $feedings): AnimalWeightChartViewModel
    {
        $orderedWeights = $weights
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $labels = $orderedWeights
            ->map(fn (AnimalWeight $weight) => optional($weight->created_at)->toDateString())
            ->values()
            ->all();

        $weightValues = $orderedWeights
            ->map(fn (AnimalWeight $weight) => (float) $weight->value)
            ->values()
            ->all();

        $orderedFeedings = $feedings
            ->filter(fn (AnimalFeeding $feeding) => (int) $feeding->feed_id > 0)
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $events = $this->buildFeedChangeEvents($orderedFeedings);
        $mapping = $this->mapFeedIdsToIndices($events);

        [$feedIndexValues, $feedNameByIndex] = $this->buildFeedIndexSeries($labels, $events, $mapping);

        return new AnimalWeightChartViewModel(
            labels: $labels,
            weightValues: $weightValues,
            feedIndexValues: $feedIndexValues,
            feedNameByIndex: $feedNameByIndex,
            feedIndexMeta: $mapping,
        );
    }

    /**
     * @param Collection<int, AnimalFeeding> $feedings
     * @return array<int, array{date: string, feed_id: int, feed_name: string}>
     */
    public function buildFeedChangeEvents(Collection $feedings): array
    {
        $events = [];
        $lastFeedId = null;

        foreach ($feedings as $feeding) {
            if (!$feeding->created_at) {
                continue;
            }

            $feedId = (int) $feeding->feed_id;
            if ($feedId <= 0) {
                continue;
            }

            if ($lastFeedId === $feedId) {
                continue;
            }

            $events[] = [
                'date' => $feeding->created_at->toDateString(),
                'feed_id' => $feedId,
                'feed_name' => $feeding->feed?->name ?? 'Karma',
            ];

            $lastFeedId = $feedId;
        }

        return $events;
    }

    /**
     * @param array<int, array{date: string, feed_id: int, feed_name: string}> $events
     * @return array{feed_id_to_index: array<int, int>, index_to_feed_name: array<int, string>}
     */
    public function mapFeedIdsToIndices(array $events, int $step = self::FEED_INDEX_STEP): array
    {
        $feedIdToIndex = [];
        $indexToFeedName = [];
        $nextIndex = $step;

        foreach ($events as $event) {
            $feedId = (int) $event['feed_id'];
            if (isset($feedIdToIndex[$feedId])) {
                continue;
            }

            $feedIdToIndex[$feedId] = $nextIndex;
            $indexToFeedName[$nextIndex] = (string) $event['feed_name'];
            $nextIndex += $step;
        }

        return [
            'feed_id_to_index' => $feedIdToIndex,
            'index_to_feed_name' => $indexToFeedName,
        ];
    }

    /**
     * @param array<int, string> $labels
     * @param array<int, array{date: string, feed_id: int, feed_name: string}> $events
     * @param array{feed_id_to_index: array<int, int>, index_to_feed_name: array<int, string>} $mapping
     * @return array{0: array<int, int|null>, 1: array<int, string|null>}
     */
    public function buildFeedIndexSeries(array $labels, array $events, array $mapping): array
    {
        $feedIndexValues = [];
        $feedNameByIndex = [];

        if (!$labels) {
            return [$feedIndexValues, $feedNameByIndex];
        }

        $eventIndex = -1;
        $currentFeedId = null;
        $currentFeedName = null;

        foreach ($labels as $labelDate) {
            while (isset($events[$eventIndex + 1]) && $events[$eventIndex + 1]['date'] <= $labelDate) {
                $eventIndex++;
                $currentFeedId = (int) $events[$eventIndex]['feed_id'];
                $currentFeedName = $events[$eventIndex]['feed_name'];
            }

            if (!$currentFeedId || !isset($mapping['feed_id_to_index'][$currentFeedId])) {
                $feedIndexValues[] = null;
                $feedNameByIndex[] = null;
                continue;
            }

            $feedIndexValues[] = $mapping['feed_id_to_index'][$currentFeedId];
            $feedNameByIndex[] = $currentFeedName;
        }

        return [$feedIndexValues, $feedNameByIndex];
    }
}
