<?php

namespace App\Application\Admin\Queries;

use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;

class GetReportsIndexQuery
{
    public function __construct(
        private readonly AdminReportHistoryRepositoryInterface $historyRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $reports = $this->historyRepository
            ->paginateNewestFirst(15)
            ->through(function ($report): array {
                return [
                    'id' => (int) $report->id,
                    'type' => $report->report_type,
                    'type_label' => $this->typeLabel((string) $report->report_type),
                    'report_name' => (string) $report->report_name,
                    'file_name' => (string) $report->file_name,
                    'generated_at' => optional($report->generated_at)->format('Y-m-d H:i'),
                    'selection_label' => $this->selectionLabel($report),
                    'item_count' => $report->item_count,
                    'preview_url' => route('admin.reports.history.preview', $report->id),
                    'download_url' => route('admin.reports.history.download', $report->id),
                    'delete_url' => route('admin.reports.destroy', $report->id),
                ];
            });

        return [
            'generator' => [
                'preview_url' => route('admin.reports.preview'),
                'generate_url' => route('admin.reports.store'),
                'defaults' => [
                    'sales_from' => now()->startOfMonth()->toDateString(),
                    'sales_to' => now()->toDateString(),
                    'daily_date' => now()->toDateString(),
                ],
            ],
            'reports' => $reports,
        ];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'sales' => 'Raport sprzedazy',
            'daily_entered_data' => 'Raport wprowadzonych danych',
            default => $type,
        };
    }

    private function selectionLabel(object $report): string
    {
        if ($report->report_date) {
            return (string) optional($report->report_date)->format('Y-m-d');
        }

        $from = optional($report->date_from)->format('Y-m-d');
        $to = optional($report->date_to)->format('Y-m-d');

        if ($from && $to) {
            return $from . ' - ' . $to;
        }

        return '-';
    }
}
