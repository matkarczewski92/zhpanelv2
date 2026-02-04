<?php

namespace App\Application\Admin\Services;

use App\Application\Admin\ViewModels\ShippingListPrintViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use Illuminate\Support\Collection;

class BuildShippingListPrintDataService
{
    /**
     * @param array<int, int> $animalIds
     */
    public function handle(array $animalIds): ShippingListPrintViewModel
    {
        $animals = Animal::query()
            ->with('animalType:id,name')
            ->whereIn('id', $animalIds)
            ->whereIn('animal_category_id', [1, 2, 4])
            ->orderBy('animal_type_id')
            ->orderBy('id')
            ->get(['id', 'name', 'sex', 'animal_type_id']);

        $groups = $animals
            ->groupBy('animal_type_id')
            ->map(function (Collection $group): array {
                /** @var Animal|null $first */
                $first = $group->first();

                $rows = $group
                    ->map(function (Animal $animal): array {
                        return [
                            'id' => (int) $animal->id,
                            'name' => $this->formatName($animal->name),
                            'sex_label' => Sex::label((int) $animal->sex),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'type_name' => (string) ($first?->animalType?->name ?? 'Brak typu'),
                    'total' => count($rows),
                    'animals' => $rows,
                ];
            })
            ->values()
            ->all();

        return new ShippingListPrintViewModel(
            groups: $groups,
            totalAnimals: $animals->count(),
            printedAt: now()->format('Y-m-d H:i'),
        );
    }

    private function formatName(?string $name): string
    {
        $value = trim(strip_tags((string) $name, '<b><i><u>'));

        return $value !== '' ? $value : '-';
    }
}

