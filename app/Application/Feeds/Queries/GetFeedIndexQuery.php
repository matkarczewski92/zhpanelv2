<?php

namespace App\Application\Feeds\Queries;

use App\Services\Panel\FeedConsumptionChartService;
use App\Services\Panel\FeedDemandPlanningService;
use App\Services\Panel\FeedService;

class GetFeedIndexQuery
{
    public function __construct(
        private readonly FeedService $feedService,
        private readonly FeedConsumptionChartService $chartService,
        private readonly FeedDemandPlanningService $planningService,
        private readonly GetFeedDeliveryDraftQuery $feedDeliveryDraftQuery
    ) {
    }

    public function handle(int $year): array
    {
        return array_merge(
            $this->feedService->getIndexData(),
            [
                'chart' => $this->chartService->getChartData($year),
                'selectedYear' => $year,
                'availableYears' => $this->chartService->getAvailableYears(),
                'planning' => $this->planningService->getInitialPlan(),
                'delivery' => $this->feedDeliveryDraftQuery->handle(),
            ]
        );
    }
}
