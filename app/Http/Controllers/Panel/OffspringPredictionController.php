<?php

namespace App\Http\Controllers\Panel;

use App\Application\LittersPlanning\Services\OffspringPredictionService;
use App\Application\LittersPlanning\Services\OffspringResultFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\OffspringPredictionRequest;
use Illuminate\Http\JsonResponse;

class OffspringPredictionController extends Controller
{
    public function predict(
        OffspringPredictionRequest $request,
        OffspringPredictionService $service,
        OffspringResultFormatter $formatter
    ): JsonResponse {
        $validated = $request->validated();
        $viewMode = (string) ($validated['view'] ?? 'summary');
        $prediction = $service->handle(
            (int) $validated['female_id'],
            (int) $validated['male_id']
        );

        $rows = $viewMode === 'full'
            ? $formatter->formatFull($prediction['rows'])
            : $formatter->formatSummary($prediction['rows']);

        $partial = $viewMode === 'full'
            ? 'panel.litters-planning._offspring_full'
            : 'panel.litters-planning._offspring_summary';

        $html = view($partial, [
            'rows' => $rows,
            'female' => $prediction['female'],
            'male' => $prediction['male'],
        ])->render();

        return response()->json([
            'view' => $viewMode,
            'rows_count' => count($rows),
            'html' => $html,
        ]);
    }
}

