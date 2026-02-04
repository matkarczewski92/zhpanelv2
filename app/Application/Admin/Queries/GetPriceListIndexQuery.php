<?php

namespace App\Application\Admin\Queries;

use App\Application\Admin\ViewModels\PriceListIndexViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;

class GetPriceListIndexQuery
{
    public function handle(array $filters): PriceListIndexViewModel
    {
        $search = trim((string) ($filters['q'] ?? ''));

        $animals = Animal::query()
            ->with([
                'offers' => fn ($query) => $query
                    ->whereNull('sold_date')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id'),
            ])
            ->whereIn('animal_category_id', [1, 2, 4])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%' . $search . '%');
                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('id')
            ->get(['id', 'name', 'sex'])
            ->map(function (Animal $animal): array {
                $offer = $animal->offers->first();
                $price = $offer?->price;
                $hasOffer = $offer !== null && $price !== null && $price !== '';

                return [
                    'id' => (int) $animal->id,
                    'name' => $this->formatName($animal->name),
                    'sex_label' => Sex::label((int) $animal->sex),
                    'price_formatted' => $this->formatPrice($price),
                    'has_offer' => $hasOffer,
                ];
            })
            ->all();

        return new PriceListIndexViewModel(
            animals: $animals,
            printUrl: route('admin.pricelist.print'),
            search: $search,
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
