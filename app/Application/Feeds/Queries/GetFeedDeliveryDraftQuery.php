<?php

namespace App\Application\Feeds\Queries;

use App\Application\Feeds\Services\FeedDeliveryDraftService;
use App\Application\Feeds\ViewModels\FeedDeliveryDraftViewModel;
use App\Models\Feed;
use Illuminate\Support\Collection;

class GetFeedDeliveryDraftQuery
{
    public function __construct(private readonly FeedDeliveryDraftService $draftService)
    {
    }

    public function handle(): FeedDeliveryDraftViewModel
    {
        $draftItems = $this->draftService->all();
        $feeds = Feed::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $feedNames = $feeds->pluck('name', 'id');

        $rows = collect($draftItems)
            ->map(function (array $item) use ($feedNames): array {
                $value = round((float) $item['value'], 2);
                $feedId = (int) $item['feed_id'];

                return [
                    'feed_id' => $feedId,
                    'name' => (string) ($feedNames[$feedId] ?? "Karma #{$feedId}"),
                    'amount' => (int) $item['amount'],
                    'value' => $value,
                    'value_label' => $this->formatCurrency($value),
                ];
            })
            ->values()
            ->all();

        $total = (float) collect($rows)->sum('value');

        return new FeedDeliveryDraftViewModel(
            availableFeeds: $this->availableFeeds($feeds, $draftItems),
            receiptRows: $rows,
            total: $total,
            totalLabel: $this->formatCurrency($total),
            hasItems: $rows !== [],
        );
    }

    /**
     * @param array<string, array{feed_id:int, amount:int, value:float}> $draftItems
     * @return array<int, array{id:int, name:string}>
     */
    private function availableFeeds(Collection $feeds, array $draftItems): array
    {
        return $feeds
            ->filter(fn (Feed $feed): bool => !array_key_exists((string) $feed->id, $draftItems))
            ->map(fn (Feed $feed): array => [
                'id' => $feed->id,
                'name' => $feed->name,
            ])
            ->values()
            ->all();
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' zl';
    }
}
