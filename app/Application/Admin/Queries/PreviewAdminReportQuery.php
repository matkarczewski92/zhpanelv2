<?php

namespace App\Application\Admin\Queries;

use App\Application\Admin\Services\BuildAdminReportDataService;

class PreviewAdminReportQuery
{
    public function __construct(
        private readonly BuildAdminReportDataService $builder
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters): array
    {
        $report = $this->builder->handle($filters);

        return [
            'status' => empty($report['rows']) ? 'empty' : 'ok',
            'message' => empty($report['rows']) ? 'Brak danych dla wybranego zakresu.' : null,
            'report' => $report,
            'from_history' => false,
        ];
    }
}
