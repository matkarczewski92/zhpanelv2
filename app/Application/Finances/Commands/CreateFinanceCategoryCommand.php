<?php

namespace App\Application\Finances\Commands;

use App\Domain\Events\FinanceCategoryCreated;
use App\Models\FinanceCategory;
use Illuminate\Support\Facades\DB;

class CreateFinanceCategoryCommand
{
    public function handle(array $data): FinanceCategory
    {
        return DB::transaction(function () use ($data): FinanceCategory {
            $category = FinanceCategory::query()->create([
                'name' => $data['name'],
            ]);

            DB::afterCommit(static function () use ($category): void {
                event(new FinanceCategoryCreated($category));
            });

            return $category;
        });
    }
}
