<?php

namespace App\Services;

use App\ViewModels\PublicAnimalProfileViewModel;
use App\Models\Animal;
use App\Models\Litter;
use App\Domain\Shared\Enums\Sex;
use Carbon\Carbon;

class PublicProfileService
{
    public function getByCode(string $code): ?PublicAnimalProfileViewModel
    {
        $animal = $this->baseQuery()
            ->where('public_profile_tag', $code)
            ->first();

        if (!$animal || !$this->isPublic($animal)) {
            return null;
        }

        $nameHtml = $this->sanitizeName($animal->name);
        $secondName = $animal->second_name ? e($animal->second_name) : '';

        $bannerUrl = $this->resolveImage($animal->photos?->firstWhere('banner_possition', '!=', null)?->url);
        $avatarUrl = $this->resolveImage($animal->photos?->firstWhere('main_profil_photo', 1)?->url);

        $gallery = $animal->photos?->filter(fn ($photo) => (int) $photo->webside === 1)
            ->map(function ($photo) {
                $url = $this->resolveImage($photo->url);
                return [
                    'url' => $url,
                    'is_featured_on_homepage' => (bool) $photo->webside,
                    'title' => basename($photo->url ?? 'foto'),
                ];
            })->values()->all() ?? [];

        $details = [
            ['label' => 'Typ', 'value' => $animal->animalType?->name ?? '-'],
            ['label' => 'Kategoria', 'value' => $animal->animalCategory?->name ?? '-'],
            ['label' => 'Płeć', 'value' => Sex::label((int) $animal->sex)],
            ['label' => 'Data urodzenia', 'value' => optional($animal->date_of_birth)->format('Y-m-d') ?? '-'],
            ['label' => 'Domyślna karma', 'value' => $animal->feed?->name ?? '-'],
            ['label' => 'Interwał karmienia', 'value' => $this->resolveFeedingInterval($animal) ?? '-'],
            ['label' => 'Miot', 'value' => $animal->litter?->litter_code ?? ($animal->litter_id ? '#' . $animal->litter_id : '-')],
            ['label' => 'Publiczny tag', 'value' => $animal->public_profile_tag ?: '-'],
        ];

        $order = ['v' => 0, 'h' => 1, 'p' => 2];
        $genotypeChips = $animal->genotypes
            ->sortBy(fn ($g) => $order[$g->type] ?? 99)
            ->map(function ($genotype) {
                return [
                    'id' => $genotype->id,
                    'label' => $genotype->category?->name ?? '-',
                    'type_code' => $genotype->type,
                    'type_label' => $this->genotypeLabel($genotype->type),
                ];
            })->values()->all();

        $litters = $this->buildLittersAsParent($animal);

        $offer = $animal->offers->first();
        $offerValue = $offer?->price ? number_format((float) $offer->price, 2, '.', '') . ' zł' : null;
        $hasReservation = (bool) ($offer?->reservations?->first());

        $feedingsTree = $this->buildFeedingsTree($animal);
        $molts = $animal->molts->map(function ($molt) {
            return [
                'date_label' => optional($molt->created_at)->format('Y-m-d') ?? '-',
            ];
        })->values()->all();
        $weightsSeries = $animal->weights->sortBy('created_at')->map(function ($w) {
            return [
                'date' => optional($w->created_at)->format('Y-m-d'),
                'value' => (float) $w->value,
            ];
        })->values()->all();
        $weights = $animal->weights->map(function ($w) {
            return [
                'date_label' => optional($w->created_at)->format('Y-m-d') ?? '-',
                'value' => (float) $w->value,
            ];
        })->values()->all();

        return new PublicAnimalProfileViewModel(
            animalTypeName: $animal->animalType?->name ?? '-',
            sexLabel: Sex::label((int) $animal->sex),
            dateOfBirth: optional($animal->date_of_birth)->format('Y-m-d'),
            litterCode: $animal->litter?->litter_code ?? '',
            nameDisplayHtml: $nameHtml,
            secondNameText: $secondName,
            bannerUrl: $bannerUrl,
            avatarUrl: $avatarUrl,
            galleryPhotos: $gallery,
            details: $details,
            genotypeChips: $genotypeChips,
            litters: $litters,
            feedingsTree: $feedingsTree,
            molts: $molts,
            weightsSeries: $weightsSeries,
            weights: $weights,
            offerValue: $offerValue,
            hasReservation: $hasReservation,
            publicProfileTag: $animal->public_profile_tag,
        );
    }

    private function isPublic(Animal $animal): bool
    {
        return (bool) ($animal->public_profile ?? $animal->public_profile_enabled ?? $animal->public_profile_flag ?? $animal->public_profile_visible ?? 0);
    }

    public function getWeightsPage(string $code, int $page = 1, int $perPage = 5): ?array
    {
        $animal = $this->findPublicAnimal($code);
        if (!$animal) {
            return null;
        }

        $paginator = $animal->weights()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(function ($w) {
                return [
                    'date_label' => optional($w->created_at)->format('Y-m-d') ?? '-',
                    'value' => (float) $w->value,
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'prev_page' => $paginator->previousPageUrl() ? $paginator->currentPage() - 1 : null,
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'total' => $paginator->total(),
            ],
        ];
    }

    public function getMoltsPage(string $code, int $page = 1, int $perPage = 5): ?array
    {
        $animal = $this->findPublicAnimal($code);
        if (!$animal) {
            return null;
        }

        $paginator = $animal->molts()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(function ($molt) {
                return [
                    'date_label' => optional($molt->created_at)->format('Y-m-d') ?? '-',
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'prev_page' => $paginator->previousPageUrl() ? $paginator->currentPage() - 1 : null,
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'total' => $paginator->total(),
            ],
        ];
    }

    private function findPublicAnimal(string $code): ?Animal
    {
        $animal = $this->baseQuery()
            ->where('public_profile_tag', $code)
            ->first();

        if (!$animal || !$this->isPublic($animal)) {
            return null;
        }

        return $animal;
    }

    private function baseQuery()
    {
        return Animal::query()
            ->with([
                'animalType',
                'animalCategory',
                'feed',
                'litter',
                'genotypes.category',
                'feedings' => function ($q) {
                    $q->with('feed')->orderByDesc('created_at');
                },
                'molts' => function ($q) {
                    $q->latest('created_at');
                },
                'weights' => function ($q) {
                    $q->orderByDesc('created_at');
                },
                'photos' => function ($q) {
                    $q->orderByDesc('main_profil_photo')->orderBy('id');
                },
                'offers' => function ($q) {
                    $q->with('reservations')->latest('created_at');
                },
            ]);
    }

    private function resolveImage(?string $url): string
    {
        $fallback = asset('Image/1_20231104195852.jpg');
        if (!$url) {
            return $fallback;
        }
        if ($this->isReadableLocal($url)) {
            return asset($url);
        }
        if ($this->isExternal($url)) {
            return $url;
        }
        return $fallback;
    }

    private function isReadableLocal(string $url): bool
    {
        $path = ltrim($url, '/');
        $full = public_path($path);
        return is_file($full) && is_readable($full);
    }

    private function isExternal(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function sanitizeName(?string $value): string
    {
        $sanitized = strip_tags((string) $value, '<b><i><u>');
        $sanitized = preg_replace('/<(b|i|u)[^>]*>/', '<$1>', $sanitized ?? '');

        return trim((string) $sanitized);
    }

    private function resolveFeedingInterval(Animal $animal): ?int
    {
        if ($animal->feed_interval) {
            return (int) $animal->feed_interval;
        }

        if ($animal->feed?->feeding_interval) {
            return (int) $animal->feed->feeding_interval;
        }

        return null;
    }

    private function genotypeLabel(?string $code): string
    {
        return match ($code) {
            'v' => 'homozygota',
            'h' => 'heterozygota',
            'p' => 'poshet',
            default => $code ?? '',
        };
    }

    private function buildLittersAsParent(Animal $animal): array
    {
        $litters = Litter::query()
            ->where(function ($query) use ($animal): void {
                $query->where('parent_male', $animal->id)
                    ->orWhere('parent_female', $animal->id);
            })
            ->where('category', '!=', 3)
            ->get(['id', 'litter_code', 'parent_male', 'parent_female', 'category', 'created_at']);

        $priority = [4 => 0, 1 => 1, 2 => 2];

        return $litters
            ->sort(function ($a, $b) use ($priority) {
                $pa = $priority[$a->category] ?? 99;
                $pb = $priority[$b->category] ?? 99;
                if ($pa === $pb) {
                    if ($a->created_at === $b->created_at) {
                        return $b->id <=> $a->id;
                    }
                    return $b->created_at <=> $a->created_at;
                }
                return $pa <=> $pb;
            })
            ->values()
            ->map(function ($litter): array {
                $code = $litter->litter_code ?: ('L#' . $litter->id);
                $categoryLabel = $this->litterCategoryLabel((int) $litter->category);

                return [
                    'code' => $code,
                    'category_label' => $categoryLabel,
                    'category_code' => $this->litterCategoryCode((int) $litter->category),
                ];
            })
            ->all();
    }

    private function litterCategoryLabel(int $category): string
    {
        return match ($category) {
            1 => 'Miot',
            2 => 'Planowany',
            4 => 'Zrealizowany',
            default => 'Miot',
        };
    }

    private function litterCategoryCode(int $category): string
    {
        return match ($category) {
            4 => 'done',
            1 => 'in-progress',
            2 => 'planned',
            default => 'planned',
        };
    }

    private function buildFeedingsTree(Animal $animal): array
    {
        return $animal->feedings
            ->sortByDesc('created_at')
            ->groupBy(fn ($feeding) => (int) optional($feeding->created_at)->format('Y'))
            ->map(function ($yearGroup, $year) {
                $months = $yearGroup
                    ->groupBy(fn ($feeding) => (int) optional($feeding->created_at)->format('m'))
                    ->sortKeysDesc()
                    ->map(function ($monthGroup, $month) use ($year) {
                        $entries = $monthGroup->sortByDesc('created_at')->map(function ($feeding) {
                            $date = optional($feeding->created_at)->format('Y-m-d');
                            return [
                                'date_display' => $date,
                                'feed_name' => $feeding->feed?->name ?? '-',
                                'quantity' => (int) $feeding->amount,
                            ];
                        })->values();

                        $first = $monthGroup->first();
                        $labelDate = optional($first?->created_at ?: Carbon::now())->locale(app()->getLocale());
                        $label = $labelDate ? $labelDate->translatedFormat('F') : str_pad($month, 2, '0', STR_PAD_LEFT);

                        return [
                            'month' => (int) $month,
                            'month_label_full' => ucfirst($label) . ' ' . (int) $year,
                            'entries' => $entries,
                        ];
                    })
                    ->values();

                return [
                    'year' => (int) $year,
                    'months' => $months,
                ];
            })
            ->values()
            ->all();
    }
}
