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
use App\Models\FinanceCategory;
use App\Models\ColorGroup;
use App\Models\EwelinkDevice;
use App\Services\Ewelink\EwelinkDeviceDataFormatter;
use App\ViewModels\Admin\AdminSettingsViewModel;

class AdminSettingsService
{
    public function __construct(private readonly EwelinkDeviceDataFormatter $ewelinkDataFormatter)
    {
    }

    public function getViewModel(?string $tab = null): AdminSettingsViewModel
    {
        $categories = AnimalCategory::orderBy('id')->get();
        $types = AnimalType::orderBy('id')->get();
        $genes = AnimalGenotypeCategory::orderBy('id')->get();
        $traits = AnimalGenotypeTrait::with('genes.category')->orderBy('id')->get();
        $winter = WinteringStage::query()
            ->orderBy('scheme')
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        $system = SystemConfig::orderBy('key')->get();
        $feeds = Feed::orderBy('id')->get();
        $financeCategories = FinanceCategory::withCount('finances')->orderBy('id')->get();
        $colorGroups = ColorGroup::withCount('animals')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $ewelinkDevices = EwelinkDevice::query()
            ->orderBy('id')
            ->get()
            ->map(function (EwelinkDevice $device): array {
                return [
                    'device' => $device,
                    'snapshot' => $this->ewelinkDataFormatter->formatForDevice($device),
                ];
            });
        $animalsWithoutGenotypes = \App\Models\Animal::query()
            ->whereDoesntHave('genotypes')
            ->orderBy('id')
            ->get(['id', 'name']);

        return new AdminSettingsViewModel(
            activeTab: $tab ?: 'categories',
            animalCategories: $categories,
            animalTypes: $types,
            genotypeCategories: $genes,
            traits: $traits,
            winteringStages: $winter,
            systemConfig: $system,
            feeds: $feeds,
            financeCategories: $financeCategories,
            colorGroups: $colorGroups,
            ewelinkDevices: $ewelinkDevices,
            animalsWithoutGenotypes: $animalsWithoutGenotypes
        );
    }
}
