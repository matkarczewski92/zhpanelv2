<?php

namespace App\Application\Animals\Commands;

use App\Models\AnimalGenotype;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteGenotypeCommand
{
    public function handle(int $animalId, int $genotypeId): void
    {
        $genotype = AnimalGenotype::query()
            ->where('id', $genotypeId)
            ->where('animal_id', $animalId)
            ->first();

        if (!$genotype) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($genotype): void {
            $genotype->delete();
        });
    }
}
