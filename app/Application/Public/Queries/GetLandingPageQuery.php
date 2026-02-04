<?php

namespace App\Application\Public\Queries;

use App\Application\Litters\Support\LitterStatusResolver;
use App\Application\Public\ViewModels\LandingPageViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Models\AnimalPhotoGallery;
use App\Models\Litter;
use Illuminate\Support\Collection;

class GetLandingPageQuery
{
    public function __construct(private readonly LitterStatusResolver $statusResolver)
    {
    }

    public function handle(): LandingPageViewModel
    {
        $gallery = AnimalPhotoGallery::query()
            ->with('animal:id,name')
            ->where('webside', 1)
            ->orderByDesc('id')
            ->limit(24)
            ->get(['id', 'animal_id', 'url'])
            ->map(fn (AnimalPhotoGallery $photo): array => [
                'url' => $this->normalizeImageUrl($photo->url),
                'title' => $this->formatPlainName($photo->animal?->name),
            ])
            ->all();

        $offers = AnimalOffer::query()
            ->with([
                'animal:id,name,sex,date_of_birth,public_profile_tag,animal_category_id,litter_id,animal_type_id',
                'animal.animalType:id,name',
                'animal.mainPhoto:id,animal_id,url',
                'animal.colorGroups:id,name,sort_order,is_active',
                'animal.litter:id,litter_code,parent_male,parent_female',
                'animal.litter.maleParent:id,name',
                'animal.litter.femaleParent:id,name',
            ])
            ->whereNull('sold_date')
            ->whereHas('animal', function ($query): void {
                $query->where('public_profile', 1);
            })
            ->orderBy('animal_id')
            ->get(['id', 'animal_id', 'price']);

        $offerGroups = $this->buildOfferGroups($offers);
        $offerColorGroups = $this->buildOfferColorGroups($offers);

        $actualYear = (int) now()->format('Y');
        $breedingPlans = Litter::query()
            ->with([
                'maleParent:id,name',
                'femaleParent:id,name',
            ])
            ->where('season', $actualYear)
            ->where('litter_code', '!=', 'PLAN')
            ->orderBy('category')
            ->orderBy('id')
            ->get(['id', 'litter_code', 'category', 'parent_male', 'parent_female', 'connection_date', 'laying_date', 'hatching_date'])
            ->map(fn (Litter $litter): array => [
                'id' => (int) $litter->id,
                'title' => (string) ($litter->litter_code ?: ('Miot #' . $litter->id)),
                'status_label' => $this->statusResolver->statusLabel($litter),
                'male_name' => $this->formatNullableName($litter->maleParent?->name),
                'female_name' => $this->formatNullableName($litter->femaleParent?->name),
            ])
            ->all();

        return new LandingPageViewModel(
            gallery: $gallery,
            offerGroups: $offerGroups,
            offerColorGroups: $offerColorGroups,
            breedingPlans: $breedingPlans,
        );
    }

    /**
     * @param Collection<int, AnimalOffer> $offers
     * @return array<int, array{
     *     type_id:int,
     *     type_name:string,
     *     title:string,
     *     male_name:string|null,
     *     female_name:string|null,
     *     offers:array<int, array{
     *         id:int,
     *         name_html:string,
     *         sex_label:string,
     *         date_of_birth:string|null,
     *         price_label:string,
     *         profile_url:string|null,
     *         photo_url:string|null
     *     }>
     * }>
     */
    private function buildOfferGroups(Collection $offers): array
    {
        return $offers
            ->groupBy(fn (AnimalOffer $offer): int => (int) ($offer->animal?->animal_type_id ?? 0))
            ->sortKeys()
            ->map(function (Collection $group, int $typeId): array {
                /** @var Animal|null $firstAnimal */
                $firstAnimal = $group->first()?->animal;
                $typeName = $firstAnimal?->animalType?->name ?? 'Nieznany typ';

                return [
                    'type_id' => $typeId,
                    'type_name' => (string) $typeName,
                    'title' => (string) $typeName,
                    'male_name' => null,
                    'female_name' => null,
                    'offers' => $this->mapOfferRows($group),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, AnimalOffer> $offers
     * @return array<int, array{
     *     id:int,
     *     name_html:string,
     *     sex_label:string,
     *     date_of_birth:string|null,
     *     price_label:string,
     *     profile_url:string|null,
     *     photo_url:string|null,
     *     color_group_ids:array<int, int>
     * }>
     */
    private function mapOfferRows(Collection $offers): array
    {
        return $offers
            ->sortBy(fn (AnimalOffer $offer): int => (int) ($offer->animal?->id ?? PHP_INT_MAX))
            ->map(function (AnimalOffer $offer): array {
                $animal = $offer->animal;

                return [
                    'id' => (int) ($animal?->id ?? 0),
                    'name_html' => $this->formatNameHtml($animal?->name),
                    'sex_label' => Sex::label((int) ($animal?->sex ?? Sex::Unknown->value)),
                    'date_of_birth' => $animal?->date_of_birth?->format('Y-m-d'),
                    'price_label' => number_format((float) ($offer->price ?? 0), 2, ',', ' ') . ' zl',
                    'profile_url' => $animal?->public_profile_tag
                        ? route('profile.show', $animal->public_profile_tag)
                        : null,
                    'photo_url' => $animal?->mainPhoto?->url
                        ? $this->normalizeImageUrl($animal->mainPhoto->url)
                        : null,
                    'color_group_ids' => $animal?->colorGroups
                        ? $animal->colorGroups
                            ->where('is_active', true)
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->values()
                            ->all()
                        : [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, AnimalOffer> $offers
     * @return array<int, array{id:int, name:string, sort_order:int}>
     */
    private function buildOfferColorGroups(Collection $offers): array
    {
        return $offers
            ->flatMap(function (AnimalOffer $offer): array {
                if (!$offer->animal?->colorGroups) {
                    return [];
                }

                return $offer->animal->colorGroups
                    ->where('is_active', true)
                    ->map(fn ($group) => [
                        'id' => (int) $group->id,
                        'name' => (string) $group->name,
                        'sort_order' => (int) ($group->sort_order ?? 0),
                    ])
                    ->values()
                    ->all();
            })
            ->unique('id')
            ->sort(function (array $a, array $b): int {
                if ($a['sort_order'] === $b['sort_order']) {
                    return strcmp($a['name'], $b['name']);
                }

                return $a['sort_order'] <=> $b['sort_order'];
            })
            ->values()
            ->map(fn (array $group) => [
                'id' => $group['id'],
                'name' => $group['name'],
                'sort_order' => $group['sort_order'],
            ])
            ->all();
    }

    private function formatName(?string $name): string
    {
        $value = trim(strip_tags((string) $name, '<b><i><u>'));

        return $value !== '' ? $value : 'Bez nazwy';
    }

    private function formatPlainName(?string $name): string
    {
        $value = trim(strip_tags((string) $name));

        return $value !== '' ? $value : 'Bez nazwy';
    }

    private function formatNameHtml(?string $name): string
    {
        $value = trim(strip_tags((string) $name, '<b><i><u>'));

        return $value !== '' ? $value : 'Bez nazwy';
    }

    private function formatNullableName(?string $name): ?string
    {
        $value = trim(strip_tags((string) $name));

        return $value !== '' ? $value : null;
    }

    private function normalizeImageUrl(?string $url): string
    {
        $value = trim((string) $url);
        if ($value === '') {
            return asset('images/landing/5.jpg');
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return asset($value);
    }
}
