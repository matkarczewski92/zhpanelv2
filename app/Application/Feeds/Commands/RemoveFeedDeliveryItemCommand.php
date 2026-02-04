<?php

namespace App\Application\Feeds\Commands;

use App\Application\Feeds\Services\FeedDeliveryDraftService;

class RemoveFeedDeliveryItemCommand
{
    public function __construct(private readonly FeedDeliveryDraftService $draftService)
    {
    }

    public function handle(int $feedId): void
    {
        $this->draftService->removeItem($feedId);
    }
}
