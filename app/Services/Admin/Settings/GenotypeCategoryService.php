<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalGenotypeTrait;
use App\Models\AnimalGenotypeTraitsDictionary;
use App\Models\AnimalGenotype;

class GenotypeCategoryService
{
    public function store(array $data): AnimalGenotypeCategory
    {
        return AnimalGenotypeCategory::create($data);
    }

    public function update(AnimalGenotypeCategory $category, array $data): AnimalGenotypeCategory
    {
        $category->update($data);
        return $category;
    }

    public function destroy(AnimalGenotypeCategory $category): array
    {
        $inUseTrait = AnimalGenotypeTrait::whereHas('genes', fn($q) => $q->where('category_id', $category->id))->exists();
        $inUseAnimal = AnimalGenotype::where('genotype_id', $category->id)->exists();

        if ($inUseTrait || $inUseAnimal) {
            return ['type' => 'error', 'message' => 'Genotyp jest używany – nie można usunąć.'];
        }
        $category->delete();
        return ['type' => 'success', 'message' => 'Genotyp usunięty.'];
    }
}
