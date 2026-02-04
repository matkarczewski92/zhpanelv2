<?php

namespace App\Application\Dashboard\ViewModels;

class DashboardViewModel
{
    /**
     * @param array<string, mixed> $management
     * @param array<int, array<string, mixed>> $litterStatuses
     * @param array<int, int> $financeYears
     * @param array<string, mixed> $financeSummary
     * @param array<string, mixed> $feedingTables
     */
    public function __construct(
        public readonly array $management,
        public readonly array $litterStatuses,
        public readonly array $financeYears,
        public readonly int $financeSelectedYear,
        public readonly array $financeSummary,
        public readonly array $feedingTables
    ) {
    }
}
