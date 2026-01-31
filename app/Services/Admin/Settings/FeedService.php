<?php

namespace App\Services\Admin\Settings;

use App\Models\Feed;
use App\Models\Animal;
use App\Models\AnimalFeeding;

class FeedService
{
    public function store(array $data): Feed
    {
        return Feed::create($data);
    }

    public function update(Feed $feed, array $data): Feed
    {
        $feed->update($data);
        return $feed;
    }

    public function destroy(Feed $feed): array
    {
        $inUse = Animal::where('feed_id', $feed->id)->exists()
            || AnimalFeeding::where('feed_id', $feed->id)->exists();
        if ($inUse) {
            return ['type' => 'error', 'message' => 'Karma używana — nie można usunąć.'];
        }
        $feed->delete();
        return ['type' => 'success', 'message' => 'Karma usunięta.'];
    }
}
