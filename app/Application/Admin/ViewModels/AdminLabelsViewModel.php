<?php

namespace App\Application\Admin\ViewModels;

class AdminLabelsViewModel
{
    /**
     * @param array<int, array<string, mixed>> $animals
     */
    public function __construct(
        public readonly array $animals,
        public readonly string $exportUrl,
        public readonly string $title
    ) {
    }
}
