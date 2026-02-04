<?php

namespace App\Application\Admin\Services;

use App\Application\Admin\ViewModels\PriceListPrintViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;

class BuildPriceListPrintDataService
{
    /**
     * @param array<int, int> $animalIds
     */
    public function handle(array $animalIds): PriceListPrintViewModel
    {
        $animals = Animal::query()
            ->with([
                'offers' => fn ($query) => $query
                    ->whereNull('sold_date')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id'),
            ])
            ->whereIn('id', $animalIds)
            ->orderBy('id')
            ->get(['id', 'name', 'sex'])
            ->map(function (Animal $animal): array {
                $price = $animal->offers->first()?->price;

                return [
                    'id' => (int) $animal->id,
                    'name' => $this->formatName($animal->name),
                    'sex_label' => Sex::label((int) $animal->sex),
                    'price_formatted' => $this->formatPrice($price),
                ];
            })
            ->all();

        return new PriceListPrintViewModel(
            animals: $animals,
            totalAnimals: count($animals),
            printedAt: now()->format('Y-m-d H:i'),
        );
    }

    private function formatName(?string $name): string
    {
        $value = trim(strip_tags((string) $name, '<b><i><u>'));

        return $value !== '' ? $value : '-';
    }

    private function formatPrice(mixed $price): string
    {
        if ($price === null || $price === '') {
            return '—';
        }

        return number_format((float) $price, 2, ',', ' ') . ' zl';
    }
}

