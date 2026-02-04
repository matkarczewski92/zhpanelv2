<?php

namespace App\Services\Genetics\GeneTypeHandlers;

interface GeneTypeHandler
{
    public function isMutantAllele(string $allele): bool;

    public function expresses(int $mutantCount): bool;
}
