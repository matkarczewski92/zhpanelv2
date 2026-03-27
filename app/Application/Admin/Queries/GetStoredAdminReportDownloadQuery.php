<?php

namespace App\Application\Admin\Queries;

use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class GetStoredAdminReportDownloadQuery
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

        if (!Storage::disk('local')->exists((string) $report->pdf_path)) {
            return [
                'status' => 'missing',
                'message' => 'Plik PDF nie istnieje na dysku.',
            ];
        }

        return [
            'status' => 'ok',
            'report' => $report,
        ];
    }
}
