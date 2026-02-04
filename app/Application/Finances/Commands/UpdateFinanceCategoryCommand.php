<?php

namespace App\Application\Finances\Commands;

use App\Domain\Events\FinanceCategoryUpdated;
use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UpdateFinanceCategoryCommand
{
    public function handle(array $data): FinanceCategory
    {
        $category = FinanceCategory::query()->find($data['id']);
        if (!$category) {
            throw new ModelNotFoundException();
        }

        return DB::transaction(function () use ($category, $data): FinanceCategory {
            $category->name = $data['name'];
            $category->save();

            DB::afterCommit(static function () use ($category): void {
                event(new FinanceCategoryUpdated($category));
            });

            return $category;
        });
    }
}
