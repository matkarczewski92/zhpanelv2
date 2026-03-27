<?php

namespace App\Application\Admin\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RenderAdminReportPdfService
{
    /**
     * @param array<string, mixed> $report
     * @return array{file_name:string,file_path:string}
     */
    public function handle(array $report): array
    {
        $baseName = $this->buildBaseFileName($report);
        $disk = Storage::disk('local');
        $directory = 'reports/admin';
        $disk->makeDirectory($directory);
        $fileName = $baseName . '.pdf';
        $filePath = $directory . '/' . $fileName;

        if ($disk->exists($filePath)) {
            $suffix = now()->format('His');
            $counter = 1;

            do {
                $fileName = $baseName . '_' . $suffix . '_' . $counter . '.pdf';
                $filePath = $directory . '/' . $fileName;
                $counter++;
            } while ($disk->exists($filePath));
        }

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'report' => $report,
        ])->setPaper('a4', 'portrait');

        $disk->put($filePath, $pdf->output());

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildBaseFileName(array $report): string
    {
        if (($report['type'] ?? null) === BuildAdminReportDataService::TYPE_SALES) {
            return 'Sale_' . ($report['filters']['date_from'] ?? 'unknown') . '_' . ($report['filters']['date_to'] ?? 'unknown');
        }

        return 'Insert_' . ($report['filters']['report_date'] ?? 'unknown');
    }
}
