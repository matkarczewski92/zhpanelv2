<?php

namespace App\Application\Public\Queries;

use App\Models\Animal;

class ResolvePublicProfileLookupQuery
{
    /**
     * @return array{status:'ok'|'not_found'|'not_public', code:string}
     */
    public function handle(string $code): array
    {
        $animal = Animal::query()
            ->select(['id', 'public_profile_tag', 'public_profile'])
            ->where('public_profile_tag', $code)
            ->first();

        if (!$animal) {
            return ['status' => 'not_found', 'code' => $code];
        }

        if (!$this->isPublic($animal)) {
            return ['status' => 'not_public', 'code' => $code];
        }

        return ['status' => 'ok', 'code' => $code];
    }

    private function isPublic(Animal $animal): bool
    {
        return (bool) ($animal->public_profile ?? 0);
    }
}

