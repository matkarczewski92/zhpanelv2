<?php

namespace App\Application\Finances\Commands;

use App\Domain\Events\FinanceCategoryDeleted;
use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteFinanceCategoryCommand
{
    public function handle(int $id): array
    {
        $category = FinanceCategory::query()->withCount('finances')->find($id);
        if (!$category) {
            throw new ModelNotFoundException();
        }

        if ($category->id <= 5) {
            return [
                'type' => 'warning',
                'message' => 'Kategorii systemowych nie mozna usuwac.',
            ];
        }

        if ((int) $category->finances_count > 0) {
            return [
                'type' => 'warning',
                'message' => 'Kategoria nie moze zostac usunieta, bo zawiera transakcje.',
            ];
        }

        DB::transaction(function () use ($category): void {
            $category->delete();

            DB::afterCommit(static function () use ($category): void {
                event(new FinanceCategoryDeleted($category));
            });
        });

        return [
            'type' => 'success',
            'message' => 'Kategorie usunieto.',
        ];
    }
}
