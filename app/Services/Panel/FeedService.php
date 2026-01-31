<?php

namespace App\Services\Panel;

use App\Models\Animal;
use App\Models\Feed;
use App\Models\Finance;

class FeedService
{
    private const INCOME_TYPES = ['income', 'przychod', 'przychód'];

    public function getIndexData(): array
    {
        $feeds = Feed::orderBy('id', 'asc')->get();

        $purchaseRows = Finance::query()
            ->select('id', 'feed_id', 'amount', 'title', 'created_at', 'type')
            ->whereNotNull('feed_id')
            ->where(function ($query) {
                $query->whereNull('type')
                    ->orWhereNotIn('type', self::INCOME_TYPES);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('feed_id');

        return [
            'feeds' => $feeds->map(fn ($feed) => [
                'id' => $feed->id,
                'name' => $feed->name,
                'feeding_interval' => $feed->feeding_interval,
                'amount' => $feed->amount,
                'last_price' => $feed->last_price,
                'animals' => Animal::query()
                    ->where('feed_id', $feed->id)
                    ->whereIn('animal_category_id', [1, 2, 4])
                    ->orderBy('id')
                    ->get(['id', 'name'])
                    ->map(fn ($animal) => [
                        'id' => $animal->id,
                        'name' => $animal->name,
                    ])
                    ->all(),
                'purchases' => ($purchaseRows[$feed->id] ?? collect())
                    ->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'title' => $purchase->title,
                        'amount' => (float) $purchase->amount,
                        'quantity' => $this->extractQuantityFromTitle($purchase->title),
                        'date' => $purchase->created_at?->format('Y-m-d') ?? '-',
                    ])
                    ->all(),
            ])->all(),
        ];
    }

    private function extractQuantityFromTitle(?string $title): int
    {
        if (!$title) {
            return 0;
        }

        if (preg_match_all('/(\\d+)\\s*szt\\b/ui', $title, $matches) && !empty($matches[1])) {
            $lastMatch = end($matches[1]);
            return (int) $lastMatch;
        }

        return 0;
    }
}
