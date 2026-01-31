<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\WeightDeleted;
use App\Models\AnimalWeight;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteWeightCommand
{
    public function handle(int $animalId, int $weightId): void
    {
        $weight = AnimalWeight::query()
            ->where('id', $weightId)
            ->where('animal_id', $animalId)
            ->first();

        if (!$weight) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($weight): void {
            $weight->delete();

            DB::afterCommit(static function () use ($weight): void {
                event(new WeightDeleted($weight));
            });
        });
    }
}
