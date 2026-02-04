<?php

namespace App\Application\Feeds\ViewModels;

class FeedDeliveryDraftViewModel
{
    /**
     * @param array<int, array{id:int, name:string}> $availableFeeds
     * @param array<int, array{feed_id:int, name:string, amount:int, value:float, value_label:string}> $receiptRows
     */
    public function __construct(
        public readonly array $availableFeeds,
        public readonly array $receiptRows,
        public readonly float $total,
        public readonly string $totalLabel,
        public readonly bool $hasItems
    ) {
    }
}
