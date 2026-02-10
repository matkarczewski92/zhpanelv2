<?php

namespace App\Application\MassData\Commands;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Domain\Events\MassDataCommitted;
use App\Models\Animal;
use App\Models\AnimalFeeding;
use App\Models\AnimalWeight;
use App\Models\Feed;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommitMassDataCommand
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $winteringCycleResolver
    ) {
    }

    /**
     * @param array{
     *     category_id:int,
     *     transaction_date:string,
     *     rows:array<int, array{
     *         animal_id:int,
     *         weight:float|null,
     *         feed_id:int|null,
     *         amount:int|null,
     *         feed_check:bool|null
     *     }>
     * } $data
     * @return array{feedings_count:int, weights_count:int, animals_count:int}
     */
    public function handle(array $data): array
    {
        $categoryId = (int) $data['category_id'];
        $transactionDate = Carbon::parse((string) $data['transaction_date'])->startOfDay();
        $rows = array_values($data['rows'] ?? []);

        return DB::transaction(function () use ($rows, $categoryId, $transactionDate): array {
            $animalIds = collect($rows)
                ->pluck('animal_id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $allowedAnimals = Animal::query()
                ->where('animal_category_id', $categoryId)
                ->whereIn('id', $animalIds)
                ->pluck('id');

            $this->ensureAllAnimalsBelongToCategory($animalIds, $allowedAnimals, $categoryId);
            $winteringActiveIds = $this->winteringCycleResolver->resolveActiveAnimalIds($animalIds->all());
            $winteringActiveMap = array_fill_keys($winteringActiveIds, true);

            $feedingsCount = 0;
            $weightsCount = 0;

            foreach ($rows as $row) {
                $animalId = (int) $row['animal_id'];
                $this->recordWeight($animalId, $row['weight'] ?? null, $transactionDate, $weightsCount);
                if (isset($winteringActiveMap[$animalId])) {
                    $row['feed_check'] = false;
                }
                $this->recordFeeding($animalId, $row, $transactionDate, $feedingsCount);
            }

            $result = [
                'feedings_count' => $feedingsCount,
                'weights_count' => $weightsCount,
                'animals_count' => $animalIds->count(),
            ];

            DB::afterCommit(function () use ($categoryId, $result): void {
                event(new MassDataCommitted(
                    categoryId: $categoryId,
                    feedingsCount: $result['feedings_count'],
                    weightsCount: $result['weights_count'],
                    animalsCount: $result['animals_count']
                ));
            });

            return $result;
        });
    }

    private function ensureAllAnimalsBelongToCategory(Collection $requestedIds, Collection $allowedIds, int $categoryId): void
    {
        if ($requestedIds->count() === $allowedIds->count()) {
            return;
        }

        throw ValidationException::withMessages([
            'rows' => "Wybrane zwierzeta nie naleza do sekcji #{$categoryId}.",
        ]);
    }

    private function recordWeight(int $animalId, mixed $weight, Carbon $transactionDate, int &$weightsCount): void
    {
        if ($weight === null || $weight === '') {
            return;
        }

        $weightModel = new AnimalWeight();
        $weightModel->animal_id = $animalId;
        $weightModel->value = (float) $weight;
        $weightModel->created_at = $transactionDate;
        $weightModel->updated_at = $transactionDate;
        $weightModel->save();

        $weightsCount++;
    }

    /**
     * @param array{
     *     feed_id:int|null,
     *     amount:int|null,
     *     feed_check:bool|null
     * } $row
     */
    private function recordFeeding(int $animalId, array $row, Carbon $transactionDate, int &$feedingsCount): void
    {
        $shouldFeed = (bool) ($row['feed_check'] ?? false);
        if (!$shouldFeed) {
            return;
        }

        $feedId = (int) ($row['feed_id'] ?? 0);
        $amount = (int) ($row['amount'] ?? 0);

        if ($feedId <= 0 || $amount <= 0) {
            throw ValidationException::withMessages([
                'rows' => "Uzupelnij karme i ilosc dla zwierzecia #{$animalId}.",
            ]);
        }

        $feed = Feed::query()->lockForUpdate()->find($feedId);
        if (!$feed) {
            throw ValidationException::withMessages([
                'rows' => "Wybrana karma #{$feedId} nie istnieje.",
            ]);
        }

        $feeding = new AnimalFeeding();
        $feeding->animal_id = $animalId;
        $feeding->feed_id = $feedId;
        $feeding->amount = $amount;
        $feeding->created_at = $transactionDate;
        $feeding->updated_at = $transactionDate;
        $feeding->save();

        $feed->amount = (int) $feed->amount - $amount;
        $feed->save();

        $feedingsCount++;
    }
}
