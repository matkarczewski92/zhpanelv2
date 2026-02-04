<?php

namespace App\Application\Animals\Queries;

use App\Application\Animals\ViewModels\AnimalProfileViewModel;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use App\Models\AnimalCategory;
use App\Models\AnimalFeeding;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalMolt;
use App\Models\AnimalOffer;
use App\Models\AnimalPhotoGallery;
use App\Models\AnimalType;
use App\Models\AnimalWeight;
use App\Models\ColorGroup;
use App\Models\Feed;
use App\Models\Litter;
use App\Services\Animal\AnimalWeightChartService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class GetAnimalProfileQuery
{
    public function __construct(private readonly AnimalWeightChartService $weightChartService)
    {
    }

    /**
     * Build the profile view model.
     */
    public function handle(int $animalId, array $navigationInput = []): AnimalProfileViewModel
    {
        $animal = Animal::query()
            ->with([
                'animalCategory',
                'animalType',
                'feed',
                'photos',
                'feedings.feed',
                'weights',
                'molts',
                'offers.reservation',
                'winterings.stage',
                'genotypes.category',
                'colorGroups',
            ])
            ->findOrFail($animalId);

        $photos = $this->buildPhotos($animal);
        $animalData = $this->buildAnimalData($animal, $photos['avatar_url'] ?? null, $photos['banner_url'] ?? null);

        $details = $this->buildDetails($animal);
        $genotype = [];

        [$feedings, $feedingTree] = $this->buildFeedings($animal);
        [$weights, $weightsSeries] = $this->buildWeights($animal);
        [$molts, $moltsCount] = $this->buildMolts($animal);

        $weightChart = $this->weightChartService->buildFromCollections(
            $animal->weights,
            $animal->feedings
        );

        $wintering = $this->buildWintering($animal);
        [$offerSummary, $reservationSummary, $offerForm, $offerExists, $reservationExists] = $this->buildOffer($animal);
        [$littersAsParent, $littersCount] = $this->buildLitters($animal);

        $feedingDefaults = $this->buildFeedingDefaults($animal);
        $feedingInterval = $this->resolveFeedingInterval($animal);
        $feedingCount = $animal->feedings->count();
        $weightsCount = $animal->weights->count();

        $genotypeChips = $this->buildGenotypeChips($animal);
        $genotypeCategoryOptions = $this->buildGenotypeCategoryOptions();
        $genotypeTypeOptions = $this->buildGenotypeTypeOptions();

        $gallerySectionId = 'gallery';
        $galleryUploadUrl = route('panel.animals.photos.store', $animal->id);
        $labelDownloadUrl = route('panel.animals.label', $animal->id);

        $isPublic = (bool) $animal->public_profile;
        $publicUrl = $isPublic && $animal->public_profile_tag
            ? route('profile.show', $animal->public_profile_tag)
            : '#';
        $togglePublicUrl = route('panel.animals.toggle-public', $animal->id);

        $edit = $this->buildEditData($animal);
        $colorGroups = $this->buildColorGroups($animal);
        $navigation = $this->buildNavigation($animal->id, $navigationInput);

        return new AnimalProfileViewModel(
            animal: $animalData,
            photos: $photos,
            details: $details,
            genotype: $genotype,
            feedings: $feedings,
            weights: $weights,
            molts: $molts,
            wintering: $wintering,
            offer: $offerSummary,
            litters: $littersAsParent,
            actions: $this->buildActions($animal, $publicUrl, $togglePublicUrl),
            feeds: Feed::orderBy('name')->get(['id', 'name'])->toArray(),
            feedingTree: $feedingTree,
            feedingDefaults: $feedingDefaults,
            feedingInterval: $feedingInterval,
            feedingCount: $feedingCount,
            weightsSeries: $weightsSeries,
            weightsCount: $weightsCount,
            weightChart: $weightChart,
            moltsCount: $moltsCount,
            genotypeChips: $genotypeChips,
            genotypeCategoryOptions: $genotypeCategoryOptions,
            genotypeTypeOptions: $genotypeTypeOptions,
            littersAsParent: $littersAsParent,
            littersCount: $littersCount,
            offerSummary: $offerSummary,
            reservationSummary: $reservationSummary,
            offerForm: $offerForm,
            offerExists: $offerExists,
            reservationExists: $reservationExists,
            gallerySectionId: $gallerySectionId,
            galleryUploadUrl: $galleryUploadUrl,
            labelDownloadUrl: $labelDownloadUrl,
            is_public_profile_enabled: $isPublic,
            public_profile_url: $publicUrl,
            toggle_public_profile_url: $togglePublicUrl,
            edit: $edit,
            colorGroups: $colorGroups,
            navigation: $navigation
        );
    }

    private function buildAnimalData(Animal $animal, ?string $avatarUrl, ?string $bannerUrl): array
    {
        $safeName = $this->sanitizeName($animal->name);

        return [
            'id' => $animal->id,
            'name' => $safeName,
            'second_name' => $animal->second_name ?? '',
            'name_display_html' => $safeName,
            'sex_label' => Sex::label((int) $animal->sex),
            'sex' => $animal->sex,
            'date_of_birth' => optional($animal->date_of_birth)->toDateString(),
            'type' => optional($animal->animalType)->name,
            'category' => optional($animal->animalCategory)->name,
            'avatar_url' => $avatarUrl,
            'avatar_initials' => $this->initials($animal->name),
            'banner_url' => $bannerUrl,
            'public_tag' => $animal->public_profile_tag,
        ];
    }

    private function buildPhotos(Animal $animal): array
    {
        $items = [];
        $main = null;
        foreach ($animal->photos->sortByDesc('created_at') as $photo) {
            $url = $this->photoUrl($photo->url);
            $items[] = [
                'id' => $photo->id,
                'url' => $url,
                'thumb_url' => $url,
                'label' => $animal->name,
                'is_main' => (bool) $photo->main_profil_photo,
                'website_visible' => (bool) $photo->webside,
                'delete_url' => route('panel.animals.photos.destroy', [$animal->id, $photo->id]),
                'set_main_url' => route('panel.animals.photos.main', [$animal->id, $photo->id]),
                'toggle_website_url' => route('panel.animals.photos.website', [$animal->id, $photo->id]),
            ];
            if ($photo->main_profil_photo) {
                $main = $url;
            }
        }

        $avatar = $main ?? ($items[0]['url'] ?? null);
        $banner = $main ?? ($items[0]['url'] ?? asset('Image/1_20231104195852.jpg'));

        return [
            'items' => $items,
            'main_url' => $main,
            'avatar_url' => $avatar,
            'banner_url' => $banner,
            'has_photos' => count($items) > 0,
        ];
    }

    private function buildDetails(Animal $animal): array
    {
        return [
            ['label' => 'Typ', 'value' => optional($animal->animalType)->name],
            ['label' => 'Kategoria', 'value' => optional($animal->animalCategory)->name],
            ['label' => 'Płeć', 'value' => Sex::label((int) $animal->sex)],
            ['label' => 'Data urodzenia', 'value' => optional($animal->date_of_birth)->format('Y-m-d')],
            ['label' => 'Domyślna karma', 'value' => optional($animal->feed)->name],
            ['label' => 'Interwał karmienia', 'value' => $this->resolveFeedingInterval($animal)],
            ['label' => 'Publiczny tag', 'value' => $animal->public_profile_tag],
            ['label' => 'Profil publiczny', 'value' => $animal->public_profile ? 'Tak' : 'Nie'],
        ];
    }

    private function buildFeedings(Animal $animal): array
    {
        $feedings = $animal->feedings()
            ->with('feed')
            ->orderByDesc('created_at')
            ->get();

        $entries = $feedings->map(function (AnimalFeeding $feeding) use ($animal) {
            $date = optional($feeding->created_at)->toDateString();
            return [
                'id' => $feeding->id,
                'date_display' => $date,
                'date_iso' => $date,
                'feed_name' => optional($feeding->feed)->name,
                'quantity' => $feeding->amount,
                'delete_url' => route('panel.animals.feedings.destroy', [$animal->id, $feeding->id]),
                'edit_payload' => [
                    'id' => $feeding->id,
                    'feed_id' => $feeding->feed_id,
                    'quantity' => $feeding->amount,
                    'date_iso' => $date,
                    'update_url' => route('panel.animals.feedings.update', [$animal->id, $feeding->id]),
                ],
            ];
        })->toArray();

        $tree = $feedings
            ->groupBy(fn($f) => optional($f->created_at)->year)
            ->sortKeysDesc()
            ->map(function (Collection $yearGroup, $year) use ($animal) {
                return [
                    'year' => $year,
                    'months' => $yearGroup
                        ->groupBy(fn($f) => optional($f->created_at)->month)
                        ->sortKeysDesc()
                        ->map(function (Collection $monthGroup, $month) use ($animal) {
                            return [
                                'month' => $month,
                                'month_label' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                                'month_label_full' => Carbon::create()->month($month)->locale('pl')->monthName . ' ' . optional($monthGroup->first()->created_at)->year,
                                'entries' => $monthGroup->sortByDesc('created_at')->map(function (AnimalFeeding $feeding) use ($animal) {
                                    $date = optional($feeding->created_at)->toDateString();
                                    return [
                                        'id' => $feeding->id,
                                        'date_iso' => $date,
                                        'date_display' => $date,
                                        'feed_name' => optional($feeding->feed)->name,
                                        'quantity' => $feeding->amount,
                                        'delete_url' => route('panel.animals.feedings.destroy', [$animal->id, $feeding->id]),
                                        'edit_payload' => [
                                            'id' => $feeding->id,
                                            'date_iso' => $date,
                                            'feed_id' => $feeding->feed_id,
                                            'quantity' => $feeding->amount,
                                            'update_url' => route('panel.animals.feedings.update', [$animal->id, $feeding->id]),
                                        ],
                                    ];
                                })->values()->all(),
                            ];
                        })->values()->all(),
                ];
            })->values()->all();

        return [$entries, $tree];
    }

    private function buildWeights(Animal $animal): array
    {
        $weights = $animal->weights()->orderByDesc('created_at')->get();

        $list = $weights->map(function (AnimalWeight $weight) use ($animal) {
            $date = optional($weight->created_at)->toDateString();
            return [
                'id' => $weight->id,
                'date_label' => $date,
                'value' => $weight->value,
                'edit_payload' => [
                    'update_url' => route('panel.animals.weights.update', [$animal->id, $weight->id]),
                    'date_iso' => $date,
                    'value' => $weight->value,
                ],
                'delete_url' => route('panel.animals.weights.destroy', [$animal->id, $weight->id]),
            ];
        })->toArray();

        $series = $weights
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn(AnimalWeight $w) => ['date' => optional($w->created_at)->toDateString(), 'value' => $w->value])
            ->values()
            ->all();

        return [$list, $series];
    }

    private function buildMolts(Animal $animal): array
    {
        $molts = $animal->molts()->orderByDesc('created_at')->get();

        $list = $molts->map(function (AnimalMolt $molt) use ($animal) {
            $date = optional($molt->created_at)->toDateString();
            return [
                'id' => $molt->id,
                'date_label' => $date,
                'edit_payload' => [
                    'update_url' => route('panel.animals.molts.update', [$animal->id, $molt->id]),
                    'date_iso' => $date,
                ],
                'delete_url' => route('panel.animals.molts.destroy', [$animal->id, $molt->id]),
            ];
        })->toArray();

        return [$list, $molts->count()];
    }

    private function buildWintering(Animal $animal): array
    {
        $wintering = $animal->winterings()->with('stage')->latest()->first();
        if (!$wintering) {
            return ['active' => false];
        }

        return [
            'active' => true,
            'stage' => optional($wintering->stage)->name,
            'started_at' => optional($wintering->created_at)->toDateString(),
        ];
    }

    private function buildOffer(Animal $animal): array
    {
        /** @var AnimalOffer|null $offer */
        $offer = $animal->offers()->latest()->first();
        if (!$offer) {
            return [null, null, [], false, false];
        }

        $reservation = $offer->reservation;

        return [
            [
                'price' => $offer->price,
                'listed_at' => optional($offer->created_at)->toDateString(),
                'updated_at' => optional($offer->updated_at)->toDateString(),
            ],
            $reservation ? [
                'reserver_name' => $reservation->reserver_name,
                'deposit_amount' => $reservation->deposit_amount,
                'reservation_date' => optional($reservation->reservation_date)->toDateString(),
                'reservation_valid_until' => optional($reservation->reservation_valid_until)->toDateString(),
                'notes' => $reservation->notes,
            ] : null,
            [], // offer form defaults not used currently
            true,
            (bool) $reservation,
        ];
    }

    private function buildLitters(Animal $animal): array
    {
        $categoryOrder = [4 => 0, 1 => 1, 2 => 2];
        $categoryLabels = [
            1 => 'W trakcie',
            2 => 'Planowany',
            4 => 'Zrealizowane',
        ];

        $litters = Litter::query()
            ->where(function ($q) use ($animal) {
                $q->where('parent_male', $animal->id)->orWhere('parent_female', $animal->id);
            })
            ->whereIn('category', array_keys($categoryOrder))
            ->orderByDesc('created_at')
            ->get();

        $sorted = $litters->sortBy(function (Litter $litter) use ($categoryOrder) {
            return [$categoryOrder[$litter->category] ?? 99, -$litter->id];
        });

        $mapped = $sorted->map(function (Litter $litter) use ($categoryLabels) {
            $code = $litter->litter_code ?: ('L#' . $litter->id);
            $categoryLabel = $categoryLabels[$litter->category] ?? '';
            return [
                'id' => $litter->id,
                'code' => $code,
                'title' => trim($code . ' ' . $categoryLabel),
                'category_code' => (string) $litter->category,
                'url' => Route::has('panel.litters.show') ? route('panel.litters.show', $litter->id) : '#',
            ];
        })->toArray();

        return [$mapped, count($mapped)];
    }

    private function buildFeedingDefaults(Animal $animal): array
    {
        $today = Carbon::now()->toDateString();
        return [
            'feed_id' => $animal->feed_id,
            'quantity' => 1,
            'date' => $today,
            'date_iso' => $today,
        ];
    }

    private function resolveFeedingInterval(Animal $animal): ?int
    {
        if ($animal->feed_interval) {
            return (int) $animal->feed_interval;
        }

        return optional($animal->feed)->feeding_interval;
    }

    private function buildGenotypeChips(Animal $animal): array
    {
        $order = ['v' => 0, 'h' => 1, 'p' => 2];
        return $animal->genotypes()
            ->with('category')
            ->get()
            ->sortBy(fn($g) => $order[$g->type] ?? 99)
            ->map(function (AnimalGenotype $genotype) use ($animal) {
                $typeLabel = match ($genotype->type) {
                    'v' => 'v-homozygota',
                    'h' => 'h-heterozygota',
                    'p' => 'p-poshet',
                    default => $genotype->type,
                };
                return [
                    'id' => $genotype->id,
                    'label' => optional($genotype->category)->name,
                    'type_code' => $genotype->type,
                    'type_label' => $typeLabel,
                    'delete_url' => route('panel.animals.genotypes.destroy', [$animal->id, $genotype->id]),
                ];
            })
            ->values()
            ->all();
    }

    private function buildGenotypeCategoryOptions(): array
    {
        return AnimalGenotypeCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    private function buildGenotypeTypeOptions(): array
    {
        return [
            ['code' => 'v', 'label' => 'v-homozygota'],
            ['code' => 'h', 'label' => 'h-heterozygota'],
            ['code' => 'p', 'label' => 'p-poshet'],
        ];
    }

    private function buildActions(Animal $animal, string $publicUrl, string $togglePublicUrl): array
    {
        return [
            [
                'label' => 'Galeria',
                'icon' => '<i class="bi bi-image"></i>',
                'url' => '#galleryModal',
                'href' => '#galleryModal',
                'modal' => '#galleryModal',
                'type' => 'modal',
                'key' => 'gallery',
            ],
            [
                'label' => 'Profil publiczny',
                'icon' => '<i class="bi bi-eye"></i>',
                'url' => $publicUrl,
                'href' => $publicUrl,
                'toggle_url' => $togglePublicUrl,
                'is_public' => (bool) $animal->public_profile,
                'type' => 'public-toggle',
                'key' => 'public-toggle',
            ],
            [
                'label' => 'Profil publiczny',
                'icon' => '<i class="bi bi-person-circle"></i>',
                'url' => $publicUrl,
                'href' => $publicUrl,
                'type' => 'link',
                'key' => 'public',
                'disabled' => !((bool) $animal->public_profile),
            ],
            [
                'label' => 'Paszport',
                'icon' => '<i class="bi bi-person-vcard"></i>',
                'url' => route('panel.animals.passport', $animal->id),
                'href' => route('panel.animals.passport', $animal->id),
                'type' => 'link',
                'key' => 'passport',
            ],
            [
                'label' => 'Etykieta',
                'icon' => '<i class="bi bi-printer"></i>',
                'url' => route('panel.animals.label', $animal->id),
                'href' => route('panel.animals.label', $animal->id),
                'type' => 'link',
                'key' => 'label',
            ],
            [
                'label' => 'Edycja',
                'icon' => '<i class="bi bi-pencil-square"></i>',
                'url' => '#animalEditModal',
                'href' => '#animalEditModal',
                'modal' => '#animalEditModal',
                'type' => 'modal',
                'key' => 'edit',
            ],
        ];
    }

    private function buildEditData(Animal $animal): array
    {
        return [
            'values' => [
                'id' => $animal->id,
                'name' => $animal->name,
                'second_name' => $animal->second_name,
                'animal_type_id' => $animal->animal_type_id,
                'category_id' => $animal->animal_category_id,
                'animal_category_id' => $animal->animal_category_id,
                'sex' => $animal->sex,
                'date_of_birth' => optional($animal->date_of_birth)->toDateString(),
                'feed_id' => $animal->feed_id,
                'feeding_interval' => $animal->feed_interval,
                'public_profile_tag' => $animal->public_profile_tag,
            ],
            'options' => [
                'types' => AnimalType::orderBy('name')->get(['id', 'name'])->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->all(),
                'categories' => AnimalCategory::orderBy('name')->get(['id', 'name'])->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->all(),
                'sex' => [
                    ['value' => Sex::Male->value, 'label' => Sex::label(Sex::Male->value)],
                    ['value' => Sex::Female->value, 'label' => Sex::label(Sex::Female->value)],
                ],
                'feeds' => Feed::orderBy('name')->get(['id', 'name'])->map(fn($f) => ['id' => $f->id, 'name' => $f->name])->all(),
            ],
            'update_url' => route('panel.animals.update', $animal->id),
            'delete_url' => route('panel.animals.delete', $animal->id),
            'is_deleted_category' => (int) $animal->animal_category_id === 5,
        ];
    }

    private function buildColorGroups(Animal $animal): array
    {
        $options = ColorGroup::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
            ])
            ->all();

        return [
            'options' => $options,
            'selected_ids' => $animal->colorGroups->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'update_url' => route('panel.animals.color-groups.sync', $animal->id),
        ];
    }

    private function buildNavigation(int $animalId, array $input): array
    {
        $rawIds = (string) ($input['nav_ids'] ?? '');
        $ids = collect(explode(',', $rawIds))
            ->map(fn ($value) => is_numeric($value) ? (int) $value : null)
            ->filter(fn ($value) => $value !== null && $value > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $index = array_search($animalId, $ids, true);
        if ($index === false) {
            return [];
        }

        $backUrl = $this->decodeBackUrl((string) ($input['nav_back'] ?? ''));
        $idsString = implode(',', $ids);

        $prevId = $index > 0 ? $ids[$index - 1] : null;
        $nextId = $index < (count($ids) - 1) ? $ids[$index + 1] : null;

        return [
            'position' => $index + 1,
            'total' => count($ids),
            'back_url' => $backUrl,
            'prev_url' => $prevId ? route('panel.animals.show', [
                'animal' => $prevId,
                'nav_ids' => $idsString,
                'nav_back' => $input['nav_back'] ?? null,
            ]) : null,
            'next_url' => $nextId ? route('panel.animals.show', [
                'animal' => $nextId,
                'nav_ids' => $idsString,
                'nav_back' => $input['nav_back'] ?? null,
            ]) : null,
        ];
    }

    private function decodeBackUrl(string $encoded): ?string
    {
        if ($encoded === '') {
            return null;
        }

        $decoded = base64_decode($encoded, true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        $host = parse_url($decoded, PHP_URL_HOST);
        if ($host !== null) {
            return null;
        }

        return str_starts_with($decoded, '/') ? $decoded : null;
    }

    private function sanitizeName(string $name): string
    {
        return strip_tags($name, '<b><i><u>');
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', $name))
            ->filter()
            ->map(fn($p) => mb_substr($p, 0, 1))
            ->take(2)
            ->implode('');
    }

    private function photoUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        if (str_starts_with($url, '/')) {
            return $url;
        }

        return asset($url);
    }
}
