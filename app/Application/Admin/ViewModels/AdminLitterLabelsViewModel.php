<?php

namespace App\Application\Admin\ViewModels;

class AdminLitterLabelsViewModel
{
    /**
     * @param array<int, array<string, mixed>> $litters
     * @param array<int, array{id:int,name:string}> $categories
     * @param array<int, int> $selectedCategoryIds
     */
    public function __construct(
        public readonly array $litters,
        public readonly array $categories,
        public readonly array $selectedCategoryIds,
        public readonly string $exportUrl,
        public readonly string $title,
    ) {
    }
}
