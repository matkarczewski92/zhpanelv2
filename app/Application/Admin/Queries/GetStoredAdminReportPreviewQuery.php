<?php

namespace App\Application\Admin\Queries;

use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;

class GetStoredAdminReportPreviewQuery
{
    public function __construct(
        private readonly AdminReportHistoryRepositoryInterface $historyRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(int $reportId): array
    {
        $report = $this->historyRepository->findOrFail($reportId);

        return [
            'status' => 'ok',
            'from_history' => true,
            'history' => [
                'id' => (int) $report->id,
                'generated_at' => optional($report->generated_at)->format('Y-m-d H:i'),
                'file_name' => (string) $report->file_name,
                'download_url' => route('admin.reports.history.download', $report->id),
            ],
            'report' => $report->report_payload ?? [],
        ];
    }
}
