<?php

namespace App\Application\Litters\ViewModels;

class LitterShowViewModel
{
    /**
     * @param array<string, mixed> $litter
     * @param array<int, array<string, mixed>> $offspring
     * @param array<int, array<string, mixed>> $pairings
     * @param array<string, int> $salesSummary
     * @param array<string, mixed> $timeline
     */
    public function __construct(
        public readonly array $litter,
        public readonly array $offspring,
        public readonly array $pairings,
        public readonly array $salesSummary,
        public readonly array $timeline
    ) {
    }
}

