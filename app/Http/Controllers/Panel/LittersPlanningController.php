<?php

namespace App\Http\Controllers\Panel;

use App\Application\LittersPlanning\Queries\GetLitterPlanningPageQuery;
use App\Application\LittersPlanning\Queries\GetPlanningFemalePreviewQuery;
use App\Application\LittersPlanning\Services\LitterRoadmapService;
use App\Application\LittersPlanning\Queries\GetPlanningSummaryQuery;
use App\Application\LittersPlanning\Services\LitterPlanningService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\LitterPlanningIndexRequest;
use App\Http\Requests\Panel\LitterPlanningPairsRequest;
use App\Http\Requests\Panel\LitterPlanningStoreRequest;
use App\Http\Requests\Panel\RealizeLitterPlanRequest;
use App\Http\Requests\Panel\StoreLitterRoadmapRequest;
use App\Http\Requests\Panel\UpdateLitterRoadmapStepStatusRequest;
use App\Http\Requests\Panel\UpdateLitterRoadmapRequest;
use App\Models\LitterPlan;
use App\Models\LitterRoadmap;
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

    public function storeRoadmap(
        StoreLitterRoadmapRequest $request,
        GetLitterPlanningPageQuery $query,
        LitterRoadmapService $service
    ): RedirectResponse {
        $validated = $request->validated();
        $snapshot = $query->buildRoadmapSnapshot(
            (string) ($validated['roadmap_expected_genes'] ?? ''),
            (int) ($validated['roadmap_generations'] ?? 0),
            isset($validated['strict_visual_only'])
                ? (bool) $validated['strict_visual_only']
                : true,
            (string) ($validated['roadmap_priority_mode'] ?? 'fastest'),
            $this->parseRootPairKeys((string) ($validated['roadmap_excluded_root_pairs'] ?? '')),
            (bool) ($validated['roadmap_generation_one_only_above_250'] ?? false)
        );

        $roadmap = $service->store([
            'name' => (string) ($validated['name'] ?? ''),
            ...$snapshot,
        ]);

        return redirect()
            ->route('panel.litters-planning.index', [
                'tab' => 'roadmaps',
                'roadmap_open_id' => $roadmap->id,
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => "Roadmap \"{$roadmap->name}\" zostala zapisana.",
            ]);
    }

    public function updateRoadmap(
        UpdateLitterRoadmapRequest $request,
        LitterRoadmap $roadmap,
        LitterRoadmapService $service
    ): RedirectResponse {
        $validated = $request->validated();
        $updated = $service->rename($roadmap, (string) ($validated['name'] ?? ''));
        $returnTab = (string) ($validated['return_tab'] ?? 'roadmaps');
        $isRoadmapTab = $returnTab === 'roadmap';

        return redirect()
            ->route('panel.litters-planning.index', [
                'tab' => $isRoadmapTab ? 'roadmap' : 'roadmaps',
                $isRoadmapTab ? 'roadmap_id' : 'roadmap_open_id' => $updated->id,
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => "Roadmap \"{$updated->name}\" zostala zaktualizowana.",
            ]);
    }

    public function refreshRoadmap(
        LitterRoadmap $roadmap,
        GetLitterPlanningPageQuery $query,
        LitterRoadmapService $service
    ): RedirectResponse {
        $snapshot = $query->buildRoadmapSnapshot(
            (string) ($roadmap->search_input ?? ''),
            (int) ($roadmap->generations ?? 0),
            true,
            'fastest'
        );

        $service->refresh($roadmap, $snapshot);

        return redirect()
            ->route('panel.litters-planning.index', [
                'tab' => 'roadmaps',
                'roadmap_open_id' => $roadmap->id,
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => "Roadmap \"{$roadmap->name}\" zostala odswiezona na podstawie aktualnej hodowli.",
            ]);
    }

    public function updateRoadmapStepStatus(
        UpdateLitterRoadmapStepStatusRequest $request,
        LitterRoadmap $roadmap,
        LitterRoadmapService $service
    ): RedirectResponse {
        $validated = $request->validated();
        $generation = (int) ($validated['generation'] ?? 0);
        $realized = (bool) ($validated['realized'] ?? false);

        $service->setGenerationRealized($roadmap, $generation, $realized);

        return redirect()
            ->route('panel.litters-planning.index', [
                'tab' => 'roadmap',
                'roadmap_id' => $roadmap->id,
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => $realized
                    ? "Pokolenie {$generation} oznaczone jako zrealizowane."
                    : "Pokolenie {$generation} odznaczone jako zrealizowane.",
            ]);
    }

    public function destroyRoadmap(LitterRoadmap $roadmap, LitterRoadmapService $service): RedirectResponse
    {
        $name = (string) $roadmap->name;
        $service->delete($roadmap);

        return redirect()
            ->route('panel.litters-planning.index', ['tab' => 'roadmaps'])
            ->with('toast', [
                'type' => 'success',
                'message' => "Roadmap \"{$name}\" zostala usunieta.",
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseRootPairKeys(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        return collect(explode(',', $trimmed))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => preg_match('/^\d+:\d+$/', $part) === 1)
            ->unique()
            ->values()
            ->all();
    }
}
