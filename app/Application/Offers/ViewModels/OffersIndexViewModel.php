<?php

namespace App\Application\Offers\ViewModels;

class OffersIndexViewModel
{
    /**
     * @param array<int, array<string, mixed>> $groups
     */
    public function __construct(
        public readonly array $groups,
        public readonly float $grandPrice = 0.0,
        public readonly float $grandDeposit = 0.0,
        public readonly string $exportLabelsUrl = '',
        public readonly string $bulkEditUrl = '',
        public readonly array $sexOptions = []
    ) {
    }
}
