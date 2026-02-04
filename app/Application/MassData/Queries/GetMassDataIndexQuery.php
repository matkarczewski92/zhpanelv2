<?php

namespace App\Application\MassData\Queries;

use App\Application\MassData\Support\MassDataFeedingDueCalculator;
use App\Application\MassData\ViewModels\MassDataIndexViewModel;
use App\Models\Animal;
use App\Models\AnimalFeeding;
use App\Models\Feed;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetMassDataIndexQuery
{
    private const SECTIONS = [
        1 => 'Zwierzeta w hodowli',
        2 => 'Mioty',
    ];

    public function __construct(private readonly MassDataFeedingDueCalculator $feedingDueCalculator)
    {
    }

    public function handle(): MassDataIndexViewModel
    {
        $lastFeedingAtSubquery = AnimalFeeding::query()
            ->select('created_at')
            ->whereColumn('animal_id', 'animals.id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);

        $animals = Animal::query()
            ->leftJoin('feeds as default_feed', 'default_feed.id', '=', 'animals.feed_id')
            ->whereIn('animals.animal_category_id', array_keys(self::SECTIONS))
            ->orderBy('animals.id')
            ->select([
                'animals.id',
                'animals.name',
                'animals.feed_id',
                'animals.feed_interval',
                'animals.animal_category_id',
            ])
            ->addSelect('default_feed.feeding_interval as default_feed_interval')
            ->selectSub($lastFeedingAtSubquery, 'last_feeding_at')
            ->get()
            ->groupBy('animal_category_id');

        $feeds = Feed::query()
            ->where('amount', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Feed $feed): array => [
                'id' => (int) $feed->id,
                'name' => (string) $feed->name,
            ])
            ->all();

        $sections = [];
        foreach (self::SECTIONS as $categoryId => $title) {
            $sections[] = [
                'category_id' => (int) $categoryId,
                'title' => $title,
                'animals' => $this->buildSectionAnimals($animals->get($categoryId, collect())),
            ];
        }

        return new MassDataIndexViewModel(
            feeds: $feeds,
            sections: $sections
        );
    }

    /**
     * @param Collection<int, Animal> $animals
     * @return array<int, array{
     *     id:int,
     *     name_html:string,
     *     profile_url:string,
     *     default_feed_id:int|null,
     *     default_amount:int,
     *     default_feed_check:bool
     * }>
     */
    private function buildSectionAnimals(Collection $animals): array
    {
        return $animals
            ->map(function (Animal $animal): array {
                $lastFeedingAt = $animal->last_feeding_at
                    ? Carbon::parse((string) $animal->last_feeding_at)
                    : null;

                $feedInterval = (int) ($animal->feed_interval ?: $animal->default_feed_interval ?: 0);
                $timeToFeed = $this->feedingDueCalculator->calculate($lastFeedingAt, $feedInterval);

                return [
                    'id' => (int) $animal->id,
                    'name_html' => $this->sanitizeName($animal->name),
                    'profile_url' => route('panel.animals.show', $animal->id),
                    'default_feed_id' => $animal->feed_id ? (int) $animal->feed_id : null,
                    'default_amount' => 1,
                    'default_feed_check' => $timeToFeed <= 0,
                ];
            })
            ->values()
            ->all();
    }

    private function sanitizeName(?string $name): string
    {
        $value = trim((string) $name);
        if ($value === '') {
            return '-';
        }

        return strip_tags($value, '<b><i><u>');
    }
}

