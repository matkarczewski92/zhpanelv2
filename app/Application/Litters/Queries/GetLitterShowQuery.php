<?php

namespace App\Application\Litters\Queries;

use App\Application\Litters\Support\LitterStatusResolver;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Application\Litters\ViewModels\LitterShowViewModel;
use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use App\Models\Litter;
use App\Services\Genetics\GenotypeCalculator;
use Illuminate\Support\Str;

class GetLitterShowQuery
{
    public function __construct(
        private readonly LitterStatusResolver $statusResolver,
        private readonly LitterTimelineCalculator $timelineCalculator,
        private readonly GenotypeCalculator $genotypeCalculator,
    ) {
    }

    /**
     * @param array<string, mixed> $planningInput
     */
    public function handle(Litter $litter, array $planningInput = []): LitterShowViewModel
    {
        $litter->loadMissing([
            'mainPhoto:id,litter_id,url,main_photo',
            'gallery:id,litter_id,url,main_photo,created_at',
            'maleParent:id,name,animal_type_id',
            'maleParent.mainPhoto:id,animal_id,url,main_profil_photo',
            'maleParent.genotypes',
            'maleParent.genotypes.category',
            'femaleParent:id,name,animal_type_id',
            'femaleParent.mainPhoto:id,animal_id,url,main_profil_photo',
            'femaleParent.genotypes',
            'femaleParent.genotypes.category',
            'adnotation:id,litter_id,adnotation',
        ]);

        $offspringCollection = Animal::query()
            ->with([
                'animalCategory:id,name',
                'offers:id,animal_id,price,sold_date',
            ])
            ->withCount('feedings')
            ->withMax('weights', 'value')
            ->where('litter_id', $litter->id)
            ->orderBy('id')
            ->get();

        $offspring = $offspringCollection
            ->map(function (Animal $animal): array {
                $sold = $animal->offers->first(fn ($offer) => !is_null($offer->sold_date));
                $latestOffer = $animal->offers->sortByDesc('id')->first();
                $hasOffer = $latestOffer !== null;

                return [
                    'id' => $animal->id,
                    'name' => trim(strip_tags((string) $animal->name)),
                    'sex' => $animal->sex_label,
                    'sex_value' => (int) $animal->sex,
                    'weight' => $animal->weights_max_value !== null ? (string) $animal->weights_max_value : '-',
                    'weight_value' => $animal->weights_max_value !== null ? (float) $animal->weights_max_value : null,
                    'feedings_count' => (int) $animal->feedings_count,
                    'date_of_birth' => $animal->date_of_birth?->format('Y-m-d') ?? '-',
                    'status' => $animal->animalCategory?->name ?? '-',
                    'is_sold' => $sold !== null,
                    'has_offer' => $hasOffer,
                    'sold_price_value' => $sold ? (float) ($sold->price ?? 0) : null,
                    'sold_price_label' => $sold ? number_format((float) ($sold->price ?? 0), 2, ',', ' ') . ' zl' : null,
                    'sold_date' => $sold?->sold_date?->format('Y-m-d'),
                    'animal_profile_url' => route('panel.animals.show', $animal),
                ];
            })
            ->all();

        $plannedOffspring = $this->buildPlannedOffspringRows($litter);

        $soldRows = collect($offspring)
            ->filter(fn (array $row) => $row['is_sold'])
            ->sortBy('sold_date')
            ->values()
            ->all();
        $soldRevenue = collect($soldRows)->sum(fn (array $row): float => (float) ($row['sold_price_value'] ?? 0));

        $galleryPhotos = collect($litter->gallery)
            ->sortByDesc(function ($photo) {
                return $photo->created_at?->timestamp ?? $photo->id ?? 0;
            })
            ->values()
            ->map(fn ($photo): array => [
                'id' => $photo->id,
                'url' => $this->resolveImageUrl($photo->url),
                'is_main' => (bool) $photo->main_photo,
                'delete_url' => route('panel.litters.gallery.destroy', [$litter->id, $photo->id]),
            ])
            ->all();

        $bannerUrl = $this->resolveBannerUrl($litter);

        $totalForSale = collect($offspring)->filter(fn (array $row) => $row['has_offer'])->count();
        $soldCount = collect($offspring)->filter(fn (array $row) => $row['is_sold'])->count();
        $planning = $this->timelineCalculator->buildPlanning($litter, $planningInput);

        return new LitterShowViewModel(
            litter: [
                'id' => $litter->id,
                'code' => $litter->litter_code,
                'category' => (int) $litter->category,
                'category_label' => $this->statusResolver->categoryLabel((int) $litter->category),
                'status_label' => $this->statusResolver->statusLabel($litter),
                'season' => $litter->season,
                'banner_image_url' => $bannerUrl,
                'gallery_photos' => $galleryPhotos,
                'parent_male' => [
                    'id' => $litter->parent_male,
                    'name' => $this->cleanName($litter->maleParent?->name),
                    'url' => $litter->parent_male ? route('panel.animals.show', $litter->parent_male) : null,
                    'avatar_url' => $this->resolveImageUrl($litter->maleParent?->mainPhoto?->url),
                ],
                'parent_female' => [
                    'id' => $litter->parent_female,
                    'name' => $this->cleanName($litter->femaleParent?->name),
                    'url' => $litter->parent_female ? route('panel.animals.show', $litter->parent_female) : null,
                    'avatar_url' => $this->resolveImageUrl($litter->femaleParent?->mainPhoto?->url),
                ],
                'planned_connection_date' => $litter->planned_connection_date?->format('Y-m-d'),
                'connection_date' => $litter->connection_date?->format('Y-m-d'),
                'laying_date' => $litter->laying_date?->format('Y-m-d'),
                'hatching_date' => $litter->hatching_date?->format('Y-m-d'),
                'laying_eggs_total' => (int) ($litter->laying_eggs_total ?? 0),
                'laying_eggs_ok' => (int) ($litter->laying_eggs_ok ?? 0),
                'hatching_eggs' => (int) ($litter->hatching_eggs ?? 0),
                'adnotation' => $litter->adnotation?->adnotation ?? '',
            ],
            offspring: $offspring,
            pairings: $plannedOffspring,
            salesSummary: [
                'offspring_count' => count($offspring),
                'sold_count' => $soldCount,
                'for_sale_count' => $totalForSale,
                'sold_revenue_label' => number_format($soldRevenue, 2, ',', ' ') . ' zl',
                'sold_rows' => $soldRows,
            ],
            timeline: [
                'estimated_laying_date' => $this->timelineCalculator->estimatedLayingDate($litter)?->format('Y-m-d'),
                'estimated_hatching_date' => $this->timelineCalculator->estimatedHatchingDate($litter)?->format('Y-m-d'),
                'planning' => $planning,
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPlannedOffspringRows(Litter $litter): array
    {
        $male = $litter->maleParent;
        $female = $litter->femaleParent;
        if (!$male || !$female) {
            return [];
        }

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $maleGenes = $this->buildAnimalGenotypeArray($male);
        $femaleGenes = $this->buildAnimalGenotypeArray($female);

        if (empty($maleGenes) && empty($femaleGenes)) {
            return [];
        }

        $rows = $this->genotypeCalculator
            ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
            ->getGenotypeFinale($maleGenes, $femaleGenes, $dictionary);

        return collect($rows)
            ->sortByDesc('percentage')
            ->values()
            ->map(function (array $row): array {
                return [
                    'percentage' => (float) ($row['percentage'] ?? 0),
                    'traits_name' => trim((string) ($row['traits_name'] ?? '')),
                    'traits_count' => (int) ($row['traits_count'] ?? 0),
                    'visual_traits' => array_values((array) ($row['visual_traits'] ?? [])),
                    'carrier_traits' => $this->sortCarrierTraitsForDisplay(array_values((array) ($row['carrier_traits'] ?? []))),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function buildAnimalGenotypeArray(Animal $animal): array
    {
        $animal->loadMissing(['genotypes.category']);

        $result = [];
        foreach ($animal->genotypes as $genotype) {
            $type = strtolower((string) ($genotype->type ?? ''));
            if ($type === 'p' || ($type !== 'h' && $type !== 'v')) {
                continue;
            }

            $category = $genotype->category;
            if (!$category) {
                continue;
            }

            $geneCode = (string) ($category->gene_code ?? '');
            $geneType = strtolower((string) ($category->gene_type ?? ''));
            if ($geneCode === '') {
                continue;
            }

            if ($type === 'h') {
                $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
                continue;
            }

            if ($geneType === 'r') {
                $result[] = [lcfirst($geneCode), lcfirst($geneCode)];
            } elseif ($geneType === 'd' || $geneType === 'i') {
                $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
            } else {
                $result[] = [ucfirst($geneCode), ucfirst($geneCode)];
            }
        }

        return $result;
    }

    private function resolveBannerUrl(Litter $litter): string
    {
        $latest = collect($litter->gallery)
            ->sortByDesc(function ($photo) {
                return $photo->created_at?->timestamp ?? $photo->id ?? 0;
            })
            ->first();
        $latestUrl = $this->resolveImageUrl($latest?->url);
        if ($latestUrl) {
            return $latestUrl;
        }

        $male = $this->resolveImageUrl($litter->maleParent?->mainPhoto?->url);
        if ($male) {
            return $male;
        }

        $female = $this->resolveImageUrl($litter->femaleParent?->mainPhoto?->url);
        if ($female) {
            return $female;
        }

        return '/src/5.jpg';
    }

    private function resolveImageUrl(?string $url): ?string
    {
        $value = trim((string) $url);
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', '/'])) {
            return $value;
        }

        return '/' . ltrim($value, '/');
    }

    private function cleanName(?string $name): string
    {
        $value = trim(strip_tags((string) $name));
        return $value !== '' ? $value : '-';
    }

    /**
     * @param array<int, string> $traits
     * @return array<int, string>
     */
    private function sortCarrierTraitsForDisplay(array $traits): array
    {
        return collect($traits)
            ->map(fn (string $trait): string => trim($trait))
            ->filter()
            ->values()
            ->map(function (string $trait, int $index): array {
                return [
                    'trait' => $trait,
                    'index' => $index,
                    'group' => $this->carrierDisplayGroup($trait),
                ];
            })
            ->sort(function (array $a, array $b): int {
                if ((int) $a['group'] === (int) $b['group']) {
                    return (int) $a['index'] <=> (int) $b['index'];
                }

                return (int) $a['group'] <=> (int) $b['group'];
            })
            ->pluck('trait')
            ->values()
            ->all();
    }

    private function carrierDisplayGroup(string $trait): int
    {
        $normalized = strtolower(trim($trait));
        if ($normalized === '') {
            return 1;
        }

        if (preg_match('/^([\d.,]+)%\s+het\s+/i', $normalized, $matches) === 1) {
            $percentRaw = str_replace(',', '.', (string) ($matches[1] ?? ''));
            if (is_numeric($percentRaw) && (float) $percentRaw < 100.0) {
                // possible (p) goes at the end
                return 2;
            }
        }

        // regular het (r) before possible
        return 1;
    }
}
