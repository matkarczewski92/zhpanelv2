<?php

namespace App\Application\Admin\Queries;

use App\Application\Admin\ViewModels\AdminLabelsViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;

class GetAdminLabelsQuery
{
    public function handle(string $exportRouteName = 'admin.labels.export', string $title = 'Drukowanie etykiet'): AdminLabelsViewModel
    {
        $animals = Animal::query()
            ->with(['animalType', 'animalCategory'])
            ->whereIn('animal_category_id', [1, 2, 4])
            ->orderBy('id')
            ->get()
            ->map(function (Animal $animal): array {
                $second = $animal->second_name ? '"' . e($animal->second_name) . '" ' : '';
                $name = trim(strip_tags($animal->name ?? ''));

                return [
                    'id' => $animal->id,
                    'name' => $second . $name,
                    'type' => $animal->animalType?->name ?? '-',
                    'sex' => Sex::label((int) $animal->sex),
                    'category' => $animal->animalCategory?->name ?? '-',
                    'public_profile_tag' => $animal->public_profile_tag ?? '-',
                    'secret_tag' => $animal->secret_tag ?? '-',
                    'date_of_birth' => optional($animal->date_of_birth)->format('Y-m-d'),
                ];
            });

        return new AdminLabelsViewModel(
            animals: $animals->all(),
            exportUrl: route($exportRouteName),
            title: $title
        );
    }
}
