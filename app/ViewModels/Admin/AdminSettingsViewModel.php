<?php

namespace App\ViewModels\Admin;

class AdminSettingsViewModel
{
    public function __construct(
        public readonly string $activeTab,
        public readonly mixed $animalCategories,
        public readonly mixed $animalTypes,
        public readonly mixed $genotypeCategories,
        public readonly mixed $traits,
        public readonly mixed $winteringStages,
        public readonly mixed $systemConfig,
        public readonly mixed $feeds,
        public readonly mixed $financeCategories,
        public readonly mixed $colorGroups,
        public readonly mixed $ewelinkDevices,
        public readonly mixed $animalsWithoutGenotypes
    ) {
    }
}
