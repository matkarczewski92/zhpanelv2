<?php

namespace App\Application\Animals\Services;

use App\Models\Animal;
use App\Domain\Shared\Enums\Sex;

class PassportService
{
    /**
     * @param array<int|string> $animalIds
     * @return array<int, array<string, mixed>>
     */
    public function buildForAnimals(array $animalIds): array
    {
        $animals = Animal::query()
            ->with(['animalType', 'litter'])
            ->whereIn('id', $animalIds)
            ->orderBy('id')
            ->get();

        return $animals->map(function (Animal $animal): array {
            return [
                'animal_id' => $animal->id,
                'public_profile_tag' => $animal->public_profile_tag ?? '-',
                'animal_type_name' => $animal->animalType?->name ?? '-',
                'date_of_birth' => optional($animal->date_of_birth)->format('Y-m-d'),
                'litter_code' => $animal->litter?->litter_code ?? '-',
                'name_display_html' => $this->sanitizeName($animal->name),
                'second_name_text' => $animal->second_name ? e($animal->second_name) : '',
                'sex_name' => Sex::label((int) $animal->sex),
                'breeder_name' => 'MaksSnake',
                'breeder_contact' => 'tel. 698 328 234',
                'breeder_email' => 'snake@makssnake.pl',
                'logo_url' => asset('src/logo_black.png'),
            ];
        })->all();
    }

    private function sanitizeName(?string $value): string
    {
        $sanitized = strip_tags((string) $value, '<b><i><u>');
        return trim((string) $sanitized);
    }
}
