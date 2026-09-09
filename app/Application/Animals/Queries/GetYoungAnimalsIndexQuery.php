<?php

namespace App\Application\Animals\Queries;

use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use Illuminate\Support\Collection;

class GetYoungAnimalsIndexQuery
{
    public function handle(): Collection
    {
        $animals = Animal::query()
            ->where('animal_category_id', 2)
            ->withExists('photos')
            ->withCount('feedings')
            ->with([
                'litter:id,litter_code',
                'offers' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id')->limit(1),
                'feedings' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id')->limit(3),
            ])
            ->orderBy('id')
            ->get();

        return $animals->map(function (Animal $animal): array {
            $offer = $animal->offers->first();

            return [
                'id' => $animal->id,
                'name' => $animal->name,
                'litter_code' => $animal->litter?->litter_code ?? 'n/d',
                'sex_label' => Sex::label((int) $animal->sex),
                'has_photo' => (bool) $animal->photos_exists,
                'has_offer' => $offer !== null,
                'feedings_ok' => $animal->feedings->count() === 3
                    && $animal->feedings->every(fn ($feeding) => $feeding->feed_id !== null
                        && ! in_array((int) $feeding->feed_id, [9, 10], true)),
                'feedings_warning' => $animal->feedings_count > 6
                    && $animal->feedings->count() === 3
                    && $animal->feedings->take(2)->every(fn ($feeding) => $feeding->feed_id !== null
                        && ! in_array((int) $feeding->feed_id, [9, 10], true))
                    && in_array((int) $animal->feedings->last()->feed_id, [9, 10], true),
                'price' => $offer?->price,
                'is_public' => (int) $animal->public_profile === 1,
                'profile_url' => route('panel.animals.show', $animal->id),
            ];
        });
    }
}