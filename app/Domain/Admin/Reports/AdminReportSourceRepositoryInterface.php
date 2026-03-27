<?php

namespace App\Domain\Admin\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface AdminReportSourceRepositoryInterface
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getSalesRows(CarbonInterface $from, CarbonInterface $to): Collection;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getDailyEnteredDataRows(CarbonInterface $day): Collection;

    /**
     * @param array<int, int> $animalIds
     * @return Collection<int, array<string, mixed>>
     */
    public function getAnimalSnapshotsByIds(array $animalIds): Collection;
}
