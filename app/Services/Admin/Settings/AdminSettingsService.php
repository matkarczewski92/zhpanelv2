<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalCategory;
use App\Models\AnimalType;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalGenotypeTrait;
use App\Models\AnimalGenotypeTraitsDictionary;
use App\Models\WinteringStage;
use App\Models\SystemConfig;
use App\Models\Feed;
use App\ViewModels\Admin\AdminSettingsViewModel;

class AdminSettingsService
{
    public function getViewModel(?string $tab = null): AdminSettingsViewModel
    {
        $categories = AnimalCategory::orderBy('id')->get();
        $types = AnimalType::orderBy('id')->get();
        $genes = AnimalGenotypeCategory::orderBy('id')->get();
        $traits = AnimalGenotypeTrait::with('genes.category')->orderBy('id')->get();
        $winter = WinteringStage::orderBy('order')->get();
        $system = SystemConfig::orderBy('key')->get();
        $feeds = Feed::orderBy('id')->get();

        return new AdminSettingsViewModel(
            activeTab: $tab ?: 'categories',
            animalCategories: $categories,
            animalTypes: $types,
            genotypeCategories: $genes,
            traits: $traits,
            winteringStages: $winter,
            systemConfig: $system,
            feeds: $feeds
        );
    }
}