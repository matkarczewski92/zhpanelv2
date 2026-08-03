<?php

namespace App\Application\Admin\Commands;

use App\Application\Animals\Services\SecretTagGenerator;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GenerateMissingAnimalSecretTagsCommand
{
    public function __construct(
        private readonly SecretTagGenerator $secretTagGenerator
    ) {}

    public function handle(): int
    {
        return DB::transaction(function (): int {
            $generated = 0;

            Animal::query()
                ->where($this->missingSecretTag(...))
                ->orderBy('id')
                ->chunkById(200, function ($animals) use (&$generated): void {
                    foreach ($animals as $animal) {
                        $updated = Animal::query()
                            ->whereKey($animal->id)
                            ->where($this->missingSecretTag(...))
                            ->update([
                                'secret_tag' => $this->secretTagGenerator->generate(),
                            ]);

                        $generated += $updated;
                    }
                });

            return $generated;
        });
    }

    private function missingSecretTag(Builder $query): void
    {
        $query
            ->whereNull('secret_tag')
            ->orWhere('secret_tag', '');
    }
}
