<?php

namespace App\Application\Api\Queries;

use App\Application\Api\Services\GalleryUrlNormalizer;
use App\Domain\Shared\Enums\Sex;
use App\Models\AnimalOffer;

class CurrentOffersQueryService
{
    private const PUBLIC_PROFILE_URL_PREFIX = 'https://www.makssnake.pl/profile/';

    public function __construct(
        private readonly GalleryUrlNormalizer $galleryUrlNormalizer
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(): array
    {
        $offers = AnimalOffer::query()
            ->with([
                'animal:id,name,sex,date_of_birth,public_profile,public_profile_tag,animal_type_id',
                'animal.animalType:id,name',
                'animal.mainPhoto:id,animal_id,url',
                'reservation',
            ])
            ->whereNull('sold_date')
            ->whereHas('animal', function ($query): void {
                $query->where('public_profile', 1);
            })
            ->orderBy('animal_id')
            ->orderByDesc('id')
            ->get(['id', 'animal_id', 'price'])
            ->unique('animal_id')
            ->values();

        return $offers
            ->map(function (AnimalOffer $offer): array {
                $animal = $offer->animal;
                $sex = (int) ($animal?->sex ?? Sex::Unknown->value);
                $tag = trim((string) ($animal?->public_profile_tag ?? ''));

                return [
                    'offer_id' => (int) $offer->id,
                    'animal_id' => (int) ($animal?->id ?? 0),
                    'type_id' => $animal?->animal_type_id !== null ? (int) $animal->animal_type_id : null,
                    'type_name' => $animal?->animalType?->name,
                    'name' => $this->formatName($animal?->name),
                    'sex' => $sex,
                    'sex_label' => Sex::label($sex),
                    'price' => $offer->price !== null ? (float) $offer->price : null,
                    'has_reservation' => $offer->reservation !== null,
                    'date_of_birth' => $animal?->date_of_birth?->format('Y-m-d'),
                    'main_photo_url' => $animal?->mainPhoto?->url
                        ? $this->galleryUrlNormalizer->normalize((string) $animal->mainPhoto->url)
                        : null,
                    'public_profile_url' => $tag !== ''
                        ? self::PUBLIC_PROFILE_URL_PREFIX . rawurlencode($tag)
                        : null,
                ];
            })
            ->all();
    }

    private function formatName(?string $name): string
    {
        $value = trim(strip_tags((string) $name));

        return $value !== '' ? $value : 'Bez nazwy';
    }
}
