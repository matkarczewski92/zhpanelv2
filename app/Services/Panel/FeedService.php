<?php

namespace App\Services\Panel;

use App\Models\Animal;
use App\Models\AnimalFeeding;
use App\Models\Feed;

class FeedService
{
    public function getIndexData(): array
    {
        $feeds = Feed::orderBy('name')->get();

        return [
            'feeds' => $feeds->map(fn($feed) => [
                'id' => $feed->id,
                'name' => $feed->name,
                'feeding_interval' => $feed->feeding_interval,
                'amount' => $feed->amount,
                'last_price' => $feed->last_price ? number_format($feed->last_price, 2, '.', ' ') . ' zł' : '-',
                'created_at' => $feed->created_at?->format('Y-m-d H:i') ?? '-',
            ])->all(),
        ];
    }

    public function store(array $data): Feed
    {
        return Feed::create($data);
    }

    public function destroy(Feed $feed): array
    {
        $inUse = Animal::where('feed_id', $feed->id)->exists()
            || AnimalFeeding::where('feed_id', $feed->id)->exists();

        if ($inUse) {
            return ['type' => 'danger', 'message' => 'Karma używana — nie można usunąć.'];
        }

        $feed->delete();

        return ['type' => 'success', 'message' => 'Karma usunięta.'];
    }
}
