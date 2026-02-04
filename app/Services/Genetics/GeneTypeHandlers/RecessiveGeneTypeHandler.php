<?php

namespace App\Services\Genetics\GeneTypeHandlers;

final class RecessiveGeneTypeHandler implements GeneTypeHandler
{
    public function isMutantAllele(string $allele): bool
    {
        $firstChar = $allele[0] ?? '';

        return $firstChar !== '' && ctype_lower($firstChar);
    }

    public function expresses(int $mutantCount): bool
    {
        return $mutantCount === 2;
    }
}
