<?php

namespace App\Http\Controllers\Animals;

use App\Application\Labels\LabelService;
use App\Http\Controllers\Controller;
use App\Models\Animal;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnimalLabelController extends Controller
{
    public function download(Animal $animal, LabelService $labels): StreamedResponse
    {
        $row = $labels->buildLabel($animal);
        $csv = "\xEF\xBB\xBF" . $labels->exportCsv(collect([$row]));
        $filename = 'etykieta_' . $animal->id . '.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
