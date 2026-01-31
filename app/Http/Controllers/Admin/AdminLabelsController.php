<?php

namespace App\Http\Controllers\Admin;

use App\Application\Labels\LabelService;
use App\Http\Controllers\Controller;
use App\Application\Admin\Queries\GetAdminLabelsQuery;
use Illuminate\Http\Request;

class AdminLabelsController extends Controller
{
    public function print(GetAdminLabelsQuery $query)
    {
        return view('admin.labels.print', [
            'vm' => $query->handle(),
        ]);
    }

    public function export(Request $request, LabelService $labels)
    {
        $ids = collect($request->input('animal_ids', []))
            ->flatMap(function ($v) {
                if (is_string($v)) {
                    return array_filter(explode(',', $v));
                }
                return (array) $v;
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
        $csv = $labels->exportCsv($rows, ';');
        $encoded = iconv('UTF-8', 'Windows-1250//TRANSLIT', $csv);
        if ($encoded !== false) {
            $csv = $encoded;
        }
        $filename = 'etykiety_admin.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=windows-1250',
        ]);
    }
}
