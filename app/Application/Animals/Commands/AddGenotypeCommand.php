<?php

namespace App\Application\Animals\Commands;

use App\Models\AnimalGenotype;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AddGenotypeCommand
{
    public function handle(array $data): AnimalGenotype
    {
        $animalId = $data['animal_id'];
        $genotypeId = $data['genotype_id'];
        $type = $data['type'];

        $exists = AnimalGenotype::query()
            ->where('animal_id', $animalId)
            ->where('genotype_id', $genotypeId)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            throw new ModelNotFoundException('Genotyp już istnieje dla tego zwierzęcia.');
        }

        return DB::transaction(function () use ($animalId, $genotypeId, $type) {
            $genotype = new AnimalGenotype([
                'animal_id' => $animalId,
                'genotype_id' => $genotypeId,
                'type' => $type,
            ]);
            $genotype->save();

            return $genotype;
        });
    }
}
