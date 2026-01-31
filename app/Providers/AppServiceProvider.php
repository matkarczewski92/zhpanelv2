<?php

namespace App\Providers;

use App\Domain\Animals\AnimalRepositoryInterface;
use App\Domain\Breeding\LitterRepositoryInterface;
use App\Domain\Offers\OfferRepositoryInterface;
use App\Domain\Winterings\WinteringRepositoryInterface;
use App\Infrastructure\Persistence\EloquentAnimalRepository;
use App\Infrastructure\Persistence\EloquentLitterRepository;
use App\Infrastructure\Persistence\EloquentOfferRepository;
use App\Infrastructure\Persistence\EloquentWinteringRepository;
use App\Models\AnimalType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AnimalRepositoryInterface::class, EloquentAnimalRepository::class);
        $this->app->bind(LitterRepositoryInterface::class, EloquentLitterRepository::class);
        $this->app->bind(OfferRepositoryInterface::class, EloquentOfferRepository::class);
        $this->app->bind(WinteringRepositoryInterface::class, EloquentWinteringRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.panel', 'panel.partials.*'], function ($view): void {
            $animalTypes = Cache::remember('panel.animal_types', 600, function () {
                return AnimalType::orderBy('name')->get();
            });

            $view->with('animalTypes', $animalTypes);
        });
    }
}
