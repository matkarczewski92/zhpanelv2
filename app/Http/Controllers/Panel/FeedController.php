<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\RecalculateFeedPlanningRequest;
use App\Http\Requests\Panel\StoreFeedRequest;
use App\Models\Feed;
use App\Services\Panel\FeedConsumptionChartService;
use App\Services\Panel\FeedDemandPlanningService;
use App\Services\Panel\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedService $service,
        private readonly FeedConsumptionChartService $chartService,
        private readonly FeedDemandPlanningService $planningService
    )
    {
    }

    public function index(): View
    {
        $year = (int) request('year', now()->year);

        return view('panel.feeds.index', array_merge(
            $this->service->getIndexData(),
            [
                'chart' => $this->chartService->getChartData($year),
                'selectedYear' => $year,
                'availableYears' => $this->chartService->getAvailableYears(),
                'planning' => $this->planningService->getInitialPlan(),
            ]
        ));
    }

    public function store(StoreFeedRequest $request)
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('panel.feeds.index')
            ->with('toast', ['type' => 'success', 'message' => 'Karma dodana.']);
    }

    public function recalculatePlanning(RecalculateFeedPlanningRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $items = $payload['items'] ?? [];

        $result = $this->planningService->recalculate($items);

        return response()->json($result);
    }

    public function destroy(Feed $feed)
    {
        $result = $this->service->destroy($feed);

        return redirect()
            ->route('panel.feeds.index')
            ->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
