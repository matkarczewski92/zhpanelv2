<?php

namespace App\Application\Feeds\Commands;

use App\Application\Feeds\Services\FeedDeliveryDraftService;

class AddFeedDeliveryItemCommand
{
    public function __construct(private readonly FeedDeliveryDraftService $draftService)
    {
    }

    public function handle(array $data): void
    {
        $this->draftService->addItem(
            feedId: (int) $data['feed_id'],
            amount: (int) $data['amount'],
            value: (float) $data['value'],
        );
    }
}
