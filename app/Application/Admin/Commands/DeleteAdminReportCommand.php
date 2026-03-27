<?php

namespace App\Application\Admin\Commands;

use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAdminReportCommand
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
        $filePath = (string) $report->pdf_path;
        $fileExisted = Storage::disk('local')->exists($filePath);

        DB::transaction(function () use ($report): void {
            $this->historyRepository->delete($report);
        });

        if ($fileExisted) {
            Storage::disk('local')->delete($filePath);

            return [
                'status' => 'ok',
                'message' => 'Raport i plik PDF zostaly usuniete.',
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'Raport usuniety. Plik PDF nie istnial juz na dysku.',
        ];
    }
}
