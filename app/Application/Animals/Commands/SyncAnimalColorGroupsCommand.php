<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\AnimalUpdated;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SyncAnimalColorGroupsCommand
{
    /**
     * @param array<int, int> $colorGroupIds
     */
    public function handle(int $animalId, array $colorGroupIds): void
    {
        $animal = Animal::query()->find($animalId);

        if (!$animal) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($animal, $colorGroupIds): void {
            $animal->colorGroups()->sync($colorGroupIds);

            DB::afterCommit(static function () use ($animal): void {
                event(new AnimalUpdated($animal));
            });
        });
    }
}

