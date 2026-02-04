<?php

namespace App\Application\Litters\Queries;

use App\Application\Litters\ViewModels\LitterFormViewModel;
use App\Models\Animal;

class GetLitterFormOptionsQuery
{
    public function handle(): LitterFormViewModel
    {
        return new LitterFormViewModel(
            maleParents: Animal::query()
                ->where('sex', 2)
                ->whereIn('animal_category_id', [1, 4])
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (Animal $animal): array => [
                    'id' => $animal->id,
                    'name' => $this->cleanName($animal->name),
                ])
                ->all(),
            femaleParents: Animal::query()
                ->where('sex', 3)
                ->whereIn('animal_category_id', [1, 4])
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (Animal $animal): array => [
                    'id' => $animal->id,
                    'name' => $this->cleanName($animal->name),
                ])
                ->all(),
            categories: [
                ['value' => 1, 'label' => 'Miot'],
                ['value' => 2, 'label' => 'Planowany'],
                ['value' => 3, 'label' => 'Szablon'],
                ['value' => 4, 'label' => 'Zrealizowany'],
            ],
        );
    }

    private function cleanName(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }
}

