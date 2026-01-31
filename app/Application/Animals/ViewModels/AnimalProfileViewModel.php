<?php

namespace App\Application\Animals\ViewModels;

class AnimalProfileViewModel
{
    /**
     * @param array<string, mixed> $animal
     * @param array<string, mixed> $photos
     * @param array<int, array{label: string, value: string|null}> $details
     * @param array<int, array{label: string, value: string|null}> $genotype
     * @param array<int, array<string, mixed>> $feedings
     * @param array<int, array<string, mixed>> $weights
     * @param array<int, array<string, mixed>> $molts
     * @param array<string, mixed> $wintering
     * @param array<string, mixed>|null $offer
     * @param array<int, array<string, mixed>> $litters
     * @param array<int, array<string, mixed>> $actions
     * @param array<int, array<string, mixed>> $feedingTree
     * @param array<string, mixed> $feedingDefaults
     * @param int|null $feedingInterval
     * @param int $feedingCount
     * @param array<int, array<string, mixed>> $weightsSeries
     * @param int $weightsCount
     * @param AnimalWeightChartViewModel $weightChart
     * @param int $moltsCount
     * @param array<int, array<string, mixed>> $genotypeChips
     * @param array<int, array<string, mixed>> $genotypeCategoryOptions
     * @param array<int, array<string, mixed>> $genotypeTypeOptions
     * @param array<int, array<string, mixed>> $littersAsParent
     * @param int $littersCount
     * @param array<string, mixed>|null $offerSummary
     * @param array<string, mixed>|null $reservationSummary
     * @param array<string, mixed> $offerForm
     * @param bool $offerExists
     * @param bool $reservationExists
     */
    public function __construct(
        public readonly array $animal,
        public readonly array $photos,
        public readonly array $details,
        public readonly array $genotype,
        public readonly array $feedings,
        public readonly array $weights,
        public readonly array $molts,
        public readonly array $wintering,
        public readonly ?array $offer,
        public readonly array $litters,
        public readonly array $actions,
        public readonly array $feeds,
        public readonly array $feedingTree = [],
        public readonly array $feedingDefaults = [],
        public readonly ?int $feedingInterval = null,
        public readonly int $feedingCount = 0,
        public readonly array $weightsSeries = [],
        public readonly int $weightsCount = 0,
        public readonly AnimalWeightChartViewModel $weightChart,
        public readonly int $moltsCount = 0,
        public readonly array $genotypeChips = [],
        public readonly array $genotypeCategoryOptions = [],
        public readonly array $genotypeTypeOptions = [],
        public readonly array $littersAsParent = [],
        public readonly int $littersCount = 0,
        public readonly ?array $offerSummary = null,
        public readonly ?array $reservationSummary = null,
        public readonly array $offerForm = [],
        public readonly bool $offerExists = false,
        public readonly bool $reservationExists = false,
        public readonly string $gallerySectionId = 'gallery',
        public readonly string $galleryUploadUrl = '',
        public readonly string $labelDownloadUrl = '',
        public readonly bool $is_public_profile_enabled = false,
        public readonly string $public_profile_url = '',
        public readonly string $toggle_public_profile_url = '',
        public readonly array $edit = []
    ) {
    }
}
