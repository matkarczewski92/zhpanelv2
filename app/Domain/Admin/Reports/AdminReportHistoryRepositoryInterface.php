<?php

namespace App\Domain\Admin\Reports;

use App\Models\AdminReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminReportHistoryRepositoryInterface
{
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): AdminReport;

    public function findOrFail(int $id): AdminReport;

    public function delete(AdminReport $report): void;
}
