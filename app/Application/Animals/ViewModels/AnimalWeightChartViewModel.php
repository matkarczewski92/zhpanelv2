<?php

namespace App\Application\Animals\ViewModels;

class AnimalWeightChartViewModel
{
    /**
     * @param array<int, string> $labels
     * @param array<int, float|int> $weightValues
     * @param array<int, int|null> $feedIndexValues
     * @param array<int, string|null> $feedNameByIndex
     * @param array{feed_id_to_index: array<int, int>, index_to_feed_name: array<int, string>} $feedIndexMeta
     */
    public function __construct(
        public readonly array $labels,
        public readonly array $weightValues,
        public readonly array $feedIndexValues,
        public readonly array $feedNameByIndex,
        public readonly array $feedIndexMeta = [
            'feed_id_to_index' => [],
            'index_to_feed_name' => [],
        ],
    ) {
    }
}
