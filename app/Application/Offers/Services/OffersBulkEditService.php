<?php

namespace App\Application\Offers\Services;

use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OffersBulkEditService
{
    /**
     * @param array<int,array{animal_id:int,name?:string,sex?:int|string,price?:float|int|string}> $items
     * @return array{updated:int}
     * @throws ValidationException
     */
    public function update(array $items): array
    {
        $cleanItems = collect($items)
            ->map(function ($item) {
                return [
                    'animal_id' => (int) ($item['animal_id'] ?? 0),
                    'name' => isset($item['name']) ? trim((string) $item['name']) : null,
                    'sex' => isset($item['sex']) ? (int) $item['sex'] : null,
                    'price' => isset($item['price']) ? (float) $item['price'] : null,
                ];
            })
            ->filter(fn ($i) => $i['animal_id'] > 0)
            ->values();

        if ($cleanItems->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Brak danych do zapisania.']);
        }

        $sexValues = collect(Sex::cases())->map->value->all();

        return DB::transaction(function () use ($cleanItems, $sexValues) {
            $animals = Animal::query()
                ->with('offers')
                ->whereIn('id', $cleanItems->pluck('animal_id')->all())
                ->get()
                ->keyBy('id');

            $updated = 0;

            foreach ($cleanItems as $item) {
                $animal = $animals[$item['animal_id']] ?? null;
                if (!$animal) {
                    continue;
                }

                if ($item['name'] !== null) {
                    if ($item['name'] === '') {
                        throw ValidationException::withMessages(['name' => 'Nazwa nie może być pusta.']);
                    }
                    $animal->name = $item['name'];
                }
                if ($item['sex'] !== null) {
                    if (!in_array($item['sex'], $sexValues, true)) {
                        throw ValidationException::withMessages(['sex' => 'Nieprawidłowa płeć.']);
                    }
                    $animal->sex = $item['sex'];
                }
                $animal->save();

                $offer = $animal->offers->first();
                if ($offer && $item['price'] !== null) {
                    if ($item['price'] < 0) {
                        throw ValidationException::withMessages(['price' => 'Cena musi być >= 0.']);
                    }
                    $offer->price = $item['price'];
                    $offer->save();
                }
                $updated++;
            }

            return ['updated' => $updated];
        });
    }
}
