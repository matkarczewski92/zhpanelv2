<?php

namespace App\Http\Controllers\Panel;

use App\Application\Winterings\Commands\StartWinteringStageCommand;
use App\Application\Winterings\Queries\GetWinteringsIndexQuery;
use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Wintering;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WinteringsController extends Controller
{
    public function index(GetWinteringsIndexQuery $query): View
    {
        return view('panel.winterings.index', [
            'page' => $query->handle(),
        ]);
    }

    public function data(GetWinteringsIndexQuery $query): JsonResponse
    {
        $page = $query->handle();
        $rows = $page['rows'] ?? [];

        return response()->json([
            'rows_html' => view('panel.winterings._rows', ['rows' => $rows])->render(),
            'count' => count($rows),
        ]);
    }

    public function advanceStage(
        Animal $animal,
        Wintering $wintering,
        StartWinteringStageCommand $command
    ): JsonResponse {
        try {
            $command->handle((int) $animal->id, (int) $wintering->id);
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $this->validationMessage($exception),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Etap zimowania rozpoczety.',
        ]);
    }

    private function validationMessage(ValidationException $exception): string
    {
        $messages = collect($exception->errors())
            ->flatten()
            ->filter()
            ->values();

        return (string) ($messages->first() ?? 'Nie udalo sie rozpoczac etapu zimowania.');
    }
}

