<?php

namespace App\Http\Controllers;

use App\Application\Offers\Queries\GetOffersIndexQuery;
use App\Application\Animals\Services\PassportService;
use App\Application\Offers\Services\OffersBulkEditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OffersController extends Controller
{
    public function index(GetOffersIndexQuery $query): View
    {
        return view('panel.offers.index', [
            'offers' => $query->handle(),
        ]);
    }

    public function bulkPassport(Request $request, PassportService $passportService)
    {
        $ids = $request->input('animal_ids', []);

        $ids = collect(is_array($ids) ? $ids : [$ids])
            ->flatMap(function ($value) {
                if (is_string($value)) {
                    return array_filter(explode(',', $value));
                }
                return (array) $value;
            })
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($ids) === 0) {
            return redirect()->back()->with('info', 'Zaznacz przynajmniej jedno zwierzę.');
        }

        $passports = $passportService->buildForAnimals($ids);

        return view('panel.passports.print', [
            'passports' => $passports,
            'bulk' => true,
        ]);
    }

    public function exportLabels(Request $request, \App\Application\Labels\LabelService $labels)
    {
        $ids = $request->input('animal_ids', []);

        $ids = collect(is_array($ids) ? $ids : [$ids])
            ->flatMap(function ($value) {
                if (is_string($value)) {
                    return array_filter(explode(',', $value));
                }
                return (array) $value;
            })
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->back()->with('toast', [
                'type' => 'warning',
                'message' => 'Zaznacz przynajmniej jedno zwierzę.',
            ]);
        }

        $rows = $labels->buildMany($ids);
        $csv = $labels->exportCsvWin1250($rows, ';');
        $filename = 'etykiety_panel.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=windows-1250',
        ]);
    }

    public function bulkEdit(Request $request, OffersBulkEditService $service): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.animal_id' => 'required|integer|min:1',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.sex' => 'nullable|integer',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);

        $result = $service->update($validated['items']);

        return response()->json(['ok' => true, 'updated' => $result['updated']]);
    }
}
