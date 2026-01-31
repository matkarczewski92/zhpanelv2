<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalCategory;
use App\Models\Animal;

class AnimalCategoryService
{
    public function store(array $data): AnimalCategory
    {
        return AnimalCategory::create($data);
    }

    public function update(AnimalCategory $category, array $data): AnimalCategory
    {
        $category->update($data);
        return $category;
    }

    public function destroy(AnimalCategory $category): array
    {
        $inUse = Animal::where('animal_category_id', $category->id)->exists();
        if ($inUse) {
            return ['type' => 'error', 'message' => 'Nie można usunąć: kategoria używana przez zwierzęta.'];
        }
        $category->delete();
        return ['type' => 'success', 'message' => 'Kategoria usunięta.'];
    }
}
