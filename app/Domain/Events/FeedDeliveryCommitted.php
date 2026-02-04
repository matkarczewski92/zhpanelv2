<?php

namespace App\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedDeliveryCommitted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<int, array{feed_id:int, feed_name:string, amount:int, value:float}> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly float $totalValue
    ) {
    }
}
