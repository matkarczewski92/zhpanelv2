<?php

namespace App\Http\Controllers\Panel;

use App\Application\LittersPlanning\Queries\GetLitterPlanningPageQuery;
use App\Application\LittersPlanning\Queries\GetPlanningFemalePreviewQuery;
use App\Application\LittersPlanning\Queries\GetPlanningSummaryQuery;
use App\Application\LittersPlanning\Services\LitterPlanningService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\LitterPlanningIndexRequest;
use App\Http\Requests\Panel\LitterPlanningPairsRequest;
use App\Http\Requests\Panel\LitterPlanningStoreRequest;
use App\Http\Requests\Panel\RealizeLitterPlanRequest;
use App\Models\LitterPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LittersPlanningController extends Controller
{
    public function index(LitterPlanningIndexRequest $request, GetLitterPlanningPageQuery $query): View
    {
        return view('panel.litters-planning.index', [
            'page' => $query->handle($request->validated()),
        ]);
    }

    public function femalePreview(
        LitterPlanningPairsRequest $request,
        GetPlanningFemalePreviewQuery $query
    ): JsonResponse {
        $validated = $request->validated();
        $femaleId = (int) ($validated['female_id'] ?? 0);

        if ($femaleId <= 0) {
            return response()->json([
                'html' => view('panel.litters-planning._planning_results', [
                    'femaleId' => null,
                    'rows' => [],
                ])->render(),
            ]);
        }

        $rows = $query->handle($femaleId, $validated['pairs'] ?? []);

        return response()->json([
            'html' => view('panel.litters-planning._planning_results', [
                'femaleId' => $femaleId,
                'rows' => $rows,
            ])->render(),
        ]);
    }

    public function summary(
        LitterPlanningPairsRequest $request,
        GetPlanningSummaryQuery $query
    ): JsonResponse {
        $pairs = $request->validated()['pairs'] ?? [];
        $summaryRows = $query->handle($pairs);

        return response()->json([
            'html' => view('panel.litters-planning._summary_modal_body', [
                'summaryRows' => $summaryRows,
            ])->render(),
        ]);
    }

    public function store(LitterPlanningStoreRequest $request, LitterPlanningService $service): RedirectResponse
    {
        $plan = $service->store($request->validated());

        return redirect()
            ->route('panel.litters-planning.index', ['tab' => 'plans'])
            ->with('toast', [
                'type' => 'success',
                'message' => "Plan \"{$plan->name}\" zostal zapisany.",
            ]);
    }

    public function realize(
        RealizeLitterPlanRequest $request,
        LitterPlan $plan,
        LitterPlanningService $service
    ): RedirectResponse {
        $created = $service->realize($plan, $request->validated()['planned_year'] ?? null);

        return redirect()
            ->route('panel.litters.index')
            ->with('toast', [
                'type' => 'success',
                'message' => "Plan zrealizowany. Dodano {$created} planowanych miotow.",
            ]);
    }

    public function destroy(LitterPlan $plan, LitterPlanningService $service): RedirectResponse
    {
        $name = (string) $plan->name;
        $service->delete($plan);

        return redirect()
            ->route('panel.litters-planning.index', ['tab' => 'plans'])
            ->with('toast', [
                'type' => 'success',
                'message' => "Plan \"{$name}\" zostal usuniety.",
            ]);
    }
}
