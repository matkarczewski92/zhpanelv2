<?php

namespace App\Application\Admin\Queries;

use App\Application\Admin\ViewModels\ShippingListIndexViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;

class GetShippingListIndexQuery
{
    public function handle(array $filters = []): ShippingListIndexViewModel
    {
        $animals = Animal::query()
            ->with('animalType:id,name')
            ->withCount([
                'offers as active_offers_count' => fn ($query) => $query->whereNull('sold_date'),
            ])
            ->whereIn('animal_category_id', [1, 2, 4])
            ->orderBy('animal_type_id')
            ->orderBy('id')
            ->get(['id', 'name', 'sex', 'animal_type_id', 'animal_category_id'])
            ->map(function (Animal $animal): array {
                return [
                    'id' => (int) $animal->id,
                    'name' => $this->formatName($animal->name),
                    'sex_label' => Sex::label((int) $animal->sex),
                    'type_name' => (string) ($animal->animalType?->name ?? 'Brak typu'),
                    'category_id' => (int) $animal->animal_category_id,
                    'has_offer' => ((int) ($animal->active_offers_count ?? 0)) > 0,
                ];
            })
            ->all();

        return new ShippingListIndexViewModel(
            animals: $animals,
            printUrl: route('admin.shipping-list.print'),
        );
    }

    private function formatName(?string $name): string
    {
        $value = trim(strip_tags((string) $name, '<b><i><u>'));

        return $value !== '' ? $value : '-';
    }
}
