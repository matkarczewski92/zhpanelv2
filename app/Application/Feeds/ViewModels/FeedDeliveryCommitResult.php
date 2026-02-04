<?php

namespace App\Application\Feeds\ViewModels;

class FeedDeliveryCommitResult
{
    public function __construct(
        public readonly int $itemsCount,
        public readonly float $totalValue
    ) {
    }
}
