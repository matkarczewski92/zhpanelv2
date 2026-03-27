<?php

namespace App\Application\Admin\Commands;

use App\Application\Admin\Services\BuildAdminReportDataService;
use App\Application\Admin\Services\RenderAdminReportPdfService;
use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GenerateQrScannerSessionReportCommand
{
    public function __construct(
        private readonly BuildAdminReportDataService $builder,
        private readonly RenderAdminReportPdfService $pdfService,
        private readonly AdminReportHistoryRepositoryInterface $historyRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        $report = $this->builder->handle([
            'report_type' => BuildAdminReportDataService::TYPE_QR_SCANNER_SESSION,
            'session_started_at' => $data['session_started_at'] ?? now()->toIso8601String(),
            'session_entries' => $data['entries'] ?? [],
        ]);

        if (empty($report['rows'])) {
            return [
                'status' => 'empty',
                'message' => 'Brak poprawnych wpisow do zapisania w podsumowaniu sesji.',
            ];
        }

        return DB::transaction(function () use ($report, $data): array {
            $pdf = $this->pdfService->handle($report);
            $generatedAt = now();
            $sessionStartedAt = CarbonImmutable::parse((string) ($report['filters']['session_started_at'] ?? $generatedAt->toIso8601String()));
            $sessionEndedAt = CarbonImmutable::parse((string) ($report['filters']['session_ended_at'] ?? $generatedAt->toIso8601String()));

            $storedReport = $this->historyRepository->create([
                'report_type' => $report['type'],
                'report_name' => $report['report_name'],
                'generated_at' => $generatedAt,
                'date_from' => $sessionStartedAt->toDateString(),
                'date_to' => $sessionEndedAt->toDateString(),
                'report_date' => $sessionStartedAt->toDateString(),
                'item_count' => $report['meta']['item_count'] ?? null,
                'file_name' => $pdf['file_name'],
                'pdf_path' => $pdf['file_path'],
                'filters_payload' => $data,
                'report_payload' => array_merge($report, [
                    'meta' => array_merge($report['meta'] ?? [], [
                        'generated_at' => $generatedAt->format('Y-m-d H:i'),
                    ]),
                ]),
            ]);

            return [
                'status' => 'ok',
                'message' => 'Podsumowanie sesji zostalo zapisane w Raportach.',
                'report_id' => (int) $storedReport->id,
                'preview_url' => route('admin.reports.history.preview', $storedReport->id),
                'reports_url' => route('admin.reports.index'),
            ];
        });
    }
}
