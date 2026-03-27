<?php

namespace App\Application\Admin\Commands;

use App\Application\Admin\Services\BuildAdminReportDataService;
use App\Application\Admin\Services\RenderAdminReportPdfService;
use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class GenerateAdminReportCommand
{
    public function __construct(
        private readonly BuildAdminReportDataService $builder,
        private readonly RenderAdminReportPdfService $pdfService,
        private readonly AdminReportHistoryRepositoryInterface $historyRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters): array
    {
        $report = $this->builder->handle($filters);

        if (empty($report['rows'])) {
            return [
                'status' => 'empty',
                'message' => 'Brak danych dla wybranego zakresu. Raport nie zostal wygenerowany.',
            ];
        }

        return DB::transaction(function () use ($report, $filters): array {
            $pdf = $this->pdfService->handle($report);
            $generatedAt = now();

            $storedReport = $this->historyRepository->create([
                'report_type' => $report['type'],
                'report_name' => $report['report_name'],
                'generated_at' => $generatedAt,
                'date_from' => $report['filters']['date_from'] ?? null,
                'date_to' => $report['filters']['date_to'] ?? null,
                'report_date' => $report['filters']['report_date'] ?? null,
                'item_count' => $report['meta']['item_count'] ?? null,
                'file_name' => $pdf['file_name'],
                'pdf_path' => $pdf['file_path'],
                'filters_payload' => $filters,
                'report_payload' => array_merge($report, [
                    'meta' => array_merge($report['meta'] ?? [], [
                        'generated_at' => $generatedAt->format('Y-m-d H:i'),
                    ]),
                ]),
            ]);

            return [
                'status' => 'ok',
                'message' => 'Raport zostal wygenerowany.',
                'report_id' => (int) $storedReport->id,
            ];
        });
    }
}
