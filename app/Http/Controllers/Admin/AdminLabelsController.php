<?php

namespace App\Http\Controllers\Admin;

use App\Application\Admin\Commands\GenerateMissingAnimalSecretTagsCommand;
use App\Application\Admin\Queries\GetAdminLabelsQuery;
use App\Application\Labels\LabelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLabelsController extends Controller
{
    public function print(GetAdminLabelsQuery $query)
    {
        return view('admin.labels.print', [
            'vm' => $query->handle(
                exportRouteName: 'admin.labels.export',
                title: 'Drukowanie etykiet'
            ),
        ]);
    }

    public function printSecret(GetAdminLabelsQuery $query)
    {
        return view('admin.labels.print', [
            'vm' => $query->handle(
                exportRouteName: 'admin.labels.secret.export',
                title: 'Etykiety (sekret)'
            ),
        ]);
    }

    public function export(Request $request, LabelService $labels)
    {
        return $this->exportLabels($request, $labels, 'etykiety_admin.csv');
    }

    public function exportSecret(Request $request, LabelService $labels)
    {
        return $this->exportLabels($request, $labels, 'etykiety_secret_admin.csv');
    }

    public function generateSecretTags(GenerateMissingAnimalSecretTagsCommand $command): RedirectResponse
    {
        $generated = $command->handle();

        if ($generated === 0) {
            return redirect()->back()->with('toast', [
                'type' => 'info',
                'message' => 'Wszystkie zwierzęta mają już Secret Tag.',
            ]);
        }

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => "Wygenerowano Secret Tag dla {$generated} zwierząt.",
        ]);
    }

    private function exportLabels(Request $request, LabelService $labels, string $filename)
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
        $csv = $labels->exportCsvWin1250($rows, ';');

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=windows-1250',
        ]);
    }
}
