<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Admin\Reports\AdminReportHistoryRepositoryInterface;
use App\Models\AdminReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAdminReportHistoryRepository implements AdminReportHistoryRepositoryInterface
{
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return AdminReport::query()
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): AdminReport
    {
        return AdminReport::query()->create($data);
    }

    public function findOrFail(int $id): AdminReport
    {
        return AdminReport::query()->findOrFail($id);
    }

    public function delete(AdminReport $report): void
    {
        $report->delete();
    }
}
