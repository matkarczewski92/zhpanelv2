<?php

namespace App\Application\Litters\ViewModels;

class LitterFormViewModel
{
    /**
     * @param array<int, array{id:int,name:string}> $maleParents
     * @param array<int, array{id:int,name:string}> $femaleParents
     * @param array<int, array{value:int,label:string}> $categories
     */
    public function __construct(
        public readonly array $maleParents,
        public readonly array $femaleParents,
        public readonly array $categories
    ) {
    }
}

