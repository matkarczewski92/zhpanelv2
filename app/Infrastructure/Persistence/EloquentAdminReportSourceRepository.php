<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Admin\Reports\AdminReportSourceRepositoryInterface;
use App\Models\Animal;
use App\Models\AnimalOffer;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EloquentAdminReportSourceRepository implements AdminReportSourceRepositoryInterface
{
    public function getSalesRows(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return AnimalOffer::query()
            ->join('animals', 'animals.id', '=', 'animal_offers.animal_id')
            ->whereBetween('animal_offers.sold_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('animal_offers.sold_date')
            ->orderBy('animals.id')
            ->get([
                'animal_offers.id',
                'animal_offers.animal_id',
                'animal_offers.price',
                'animal_offers.sold_date',
                'animals.name',
                'animals.second_name',
                'animals.public_profile_tag',
            ])
            ->map(function (AnimalOffer $offer): array {
                return [
                    'animal_id' => (int) $offer->animal_id,
                    'animal_name' => $this->formatAnimalName($offer->name, $offer->second_name),
                    'public_tag' => $offer->public_profile_tag ?: null,
                    'sale_date' => $offer->sold_date?->format('Y-m-d'),
                    'sale_price' => (float) ($offer->price ?? 0),
                    'sale_price_label' => $this->formatCurrency((float) ($offer->price ?? 0)),
                ];
            })
            ->values();
    }

    public function getDailyEnteredDataRows(CarbonInterface $day): Collection
    {
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        return Animal::query()
            ->with([
                'feedings' => function ($query) use ($start, $end): void {
                    $query
                        ->with('feed:id,name')
                        ->whereBetween('created_at', [$start, $end])
                        ->orderBy('created_at');
                },
                'weights' => function ($query) use ($start, $end): void {
                    $query
                        ->whereBetween('created_at', [$start, $end])
                        ->orderBy('created_at');
                },
                'molts' => function ($query) use ($start, $end): void {
                    $query
                        ->whereBetween('created_at', [$start, $end])
                        ->orderBy('created_at');
                },
            ])
            ->where(function ($query) use ($start, $end): void {
                $query
                    ->whereHas('feedings', fn ($feedingQuery) => $feedingQuery->whereBetween('created_at', [$start, $end]))
                    ->orWhereHas('weights', fn ($weightQuery) => $weightQuery->whereBetween('created_at', [$start, $end]))
                    ->orWhereHas('molts', fn ($moltQuery) => $moltQuery->whereBetween('created_at', [$start, $end]));
            })
            ->orderBy('id')
            ->get(['id', 'name', 'second_name', 'public_profile_tag'])
            ->map(function (Animal $animal): array {
                return [
                    'animal_id' => (int) $animal->id,
                    'animal_name' => $this->formatAnimalName($animal->name, $animal->second_name),
                    'public_tag' => $animal->public_profile_tag ?: null,
                    'feedings' => $animal->feedings->map(function ($feeding): array {
                        $feedName = trim((string) optional($feeding->feed)->name);

                        return [
                            'feed_type' => $feedName !== '' ? $feedName : '-',
                            'quantity' => (int) ($feeding->amount ?? 0),
                            'time' => optional($feeding->created_at)?->format('H:i'),
                            'label' => trim(($feedName !== '' ? $feedName : '-') . ' x' . (int) ($feeding->amount ?? 0)),
                        ];
                    })->values()->all(),
                    'weights' => $animal->weights->map(function ($weight): array {
                        $time = optional($weight->created_at)?->format('H:i');
                        $value = (float) ($weight->value ?? 0);

                        return [
                            'value' => $value,
                            'time' => $time,
                            'label' => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . ' g' . ($time ? ' ' . $time : ''),
                        ];
                    })->values()->all(),
                    'molts' => $animal->molts->map(function ($molt): array {
                        $time = optional($molt->created_at)?->format('H:i');

                        return [
                            'time' => $time,
                            'label' => 'Wpis dodany' . ($time ? ' ' . $time : ''),
                        ];
                    })->values()->all(),
                ];
            })
            ->values();
    }

    private function formatAnimalName(?string $name, ?string $secondName): string
    {
        $main = trim(strip_tags((string) $name, '<b><i><u><strong><em><br>'));
        $second = trim(strip_tags((string) $secondName));
        $label = trim($second !== '' ? e($second) . ' ' . $main : $main);

        return $label !== '' ? $label : '-';
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' zl';
    }
}
