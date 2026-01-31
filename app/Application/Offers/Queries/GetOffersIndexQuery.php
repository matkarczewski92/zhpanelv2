<?php

namespace App\Application\Offers\Queries;

use App\Application\Offers\ViewModels\OffersIndexViewModel;
use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Domain\Shared\Enums\Sex;
use Illuminate\Support\Collection;

class GetOffersIndexQuery
{
    public function handle(): OffersIndexViewModel
    {
        $offers = AnimalOffer::query()
            ->with(['animal.animalType', 'animal.animalCategory', 'reservations'])
            ->whereNull('sold_date')
            ->whereHas('animal', function ($q) {
                $q->whereIn('animal_category_id', [1, 2]);
            })
            ->latest('updated_at')
            ->get();

        $grandPrice = 0;
        $grandDeposit = 0;

        $grouped = $offers->groupBy(function (AnimalOffer $offer) {
            return $offer->animal?->animal_type_id ?? 0;
        })->sortKeys()->map(function (Collection $group) {
            $sorted = $group->sortBy(fn (AnimalOffer $offer) => $offer->animal?->id ?? $offer->id);
            $first = $sorted->first();
            $typeName = $first?->animal?->animalType?->name ?? 'Nieznany typ';
            $sumPrice = 0;
            $sumDeposit = 0;

            return [
                'type_name' => $typeName,
                'rows' => $sorted->map(function (AnimalOffer $offer) use (&$sumPrice, &$sumDeposit) {
                    $animal = $offer->animal;
                    $reservation = $offer->reservations?->first();
                    $sexLabel = $animal ? \App\Domain\Shared\Enums\Sex::label((int) $animal->sex) : '-';
                    $priceValue = $offer->price ?? 0;
                    $depositValue = $reservation?->deposit ?? 0;
                    $sumPrice += $priceValue;
                    $sumDeposit += $depositValue;

                    $namePlain = $animal?->name ?? '-';
                    $nameHtml = $animal?->name_display_html ?? e($namePlain);
                    $secondName = $animal?->second_name ? e($animal->second_name) : null;

                    return [
                        'animal_id' => $animal->id ?? null,
                        'animal_name_html' => $nameHtml,
                        'animal_name_plain' => $namePlain,
                        'second_name' => $secondName,
                        'sex' => $sexLabel,
                        'sex_value' => $animal?->sex,
                        'price_value' => $priceValue,
                        'price' => $offer->price ? number_format($offer->price, 2, '.', ' ') . ' zł' : '-',
                        'date' => optional($offer->updated_at ?? $offer->created_at)->format('Y-m-d'),
                        'reserver' => $reservation?->booker ?? '-',
                        'reservation_date' => optional($reservation?->created_at)->format('Y-m-d') ?? '-',
                        'deposit_value' => $depositValue,
                        'deposit' => $depositValue ? number_format($depositValue, 2, '.', ' ') . ' zł' : '—',
                        'public_enabled' => (bool) ($animal?->public_profile ?? false),
                        'public_toggle_url' => $animal ? route('panel.animals.offer.toggle-public', $animal->id) : '#',
                        'profile_url' => $animal ? route('panel.animals.show', $animal) : '#',
                        'edit_payload' => [
                            'action' => $animal ? route('panel.animals.offer.store', $animal) : '#',
                            'price' => $offer->price,
                            'sold_at' => optional($offer->sold_date)->format('Y-m-d'),
                            'public_profile_enabled' => (bool) ($animal?->public_profile ?? false),
                            'reserver_name' => $reservation?->booker,
                            'deposit_amount' => $reservation?->deposit,
                            'reservation_valid_until' => optional($reservation?->expiration_date)->format('Y-m-d'),
                            'notes' => $reservation?->adnotations,
                            'delete_reservation_url' => $animal ? route('panel.animals.offer.reservation.destroy', $animal) : null,
                            'delete_offer_url' => $animal ? route('panel.animals.offer.destroy', $animal) : null,
                            'sell_url' => $animal ? route('panel.animals.offer.sell', $animal) : null,
                        ],
                    ];
                })->values(),
                'sum_price_value' => $sumPrice,
                'sum_deposit_value' => $sumDeposit,
                'sum_price' => $sumPrice ? number_format($sumPrice, 2, '.', ' ') . ' zł' : '0 zł',
                'sum_deposit' => $sumDeposit ? number_format($sumDeposit, 2, '.', ' ') . ' zł' : '0 zł',
            ];
        })->values();

        $grandPrice = $grouped->sum('sum_price_value');
        $grandDeposit = $grouped->sum('sum_deposit_value');

        return new OffersIndexViewModel(
            groups: $grouped->all(),
            grandPrice: $grandPrice,
            grandDeposit: $grandDeposit,
            exportLabelsUrl: route('panel.offers.labels.export'),
            bulkEditUrl: route('panel.offers.bulkEdit'),
            sexOptions: collect(Sex::cases())->map(fn ($sex) => ['value' => $sex->value, 'label' => Sex::label($sex->value)])->values()->all()
        );
    }
}
