<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalGenotypeTraitsDictionary;
use App\Models\AnimalGenotypeTrait;

class TraitDictionaryService
{
    public function addGene(AnimalGenotypeTrait $trait, int $categoryId): array
    {
        $exists = AnimalGenotypeTraitsDictionary::where('trait_id', $trait->id)
            ->where('category_id', $categoryId)
            ->exists();
        if ($exists) {
            return ['type' => 'warning', 'message' => 'Gen już przypisany.'];
        }

        AnimalGenotypeTraitsDictionary::create([
            'trait_id' => $trait->id,
            'category_id' => $categoryId,
        ]);

        $trait->update(['number_of_traits' => AnimalGenotypeTraitsDictionary::where('trait_id', $trait->id)->count()]);

        return ['type' => 'success', 'message' => 'Gen dodany do traitu.'];
    }

    public function removeGene(AnimalGenotypeTrait $trait, AnimalGenotypeTraitsDictionary $dictionary): void
    {
        $dictionary->delete();
        $trait->update(['number_of_traits' => AnimalGenotypeTraitsDictionary::where('trait_id', $trait->id)->count()]);
    }
}
