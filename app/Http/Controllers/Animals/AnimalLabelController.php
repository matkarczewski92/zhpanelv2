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
        $csv = $labels->exportCsv(collect([$row]), ';');
        $encoded = iconv('UTF-8', 'Windows-1250//TRANSLIT', $csv);
        if ($encoded !== false) {
            $csv = $encoded;
        }
        $filename = 'etykieta_' . $animal->id . '.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=windows-1250',
        ]);
    }
}
