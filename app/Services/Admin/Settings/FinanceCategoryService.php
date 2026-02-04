<?php

namespace App\Services\Admin\Settings;

use App\Models\FinanceCategory;

class FinanceCategoryService
{
    public function store(array $data): FinanceCategory
    {
        return FinanceCategory::query()->create([
            'name' => $data['name'],
        ]);
    }

    public function update(FinanceCategory $category, array $data): FinanceCategory
    {
        $category->name = $data['name'];
        $category->save();

        return $category;
    }

    public function destroy(FinanceCategory $category): array
    {
        if ($category->id <= 5) {
            return ['type' => 'warning', 'message' => 'Kategorii systemowych nie mozna usuwac.'];
        }

        $hasTransactions = $category->finances()->exists();
        if ($hasTransactions) {
            return ['type' => 'warning', 'message' => 'Kategoria uzywana - nie mozna usunac.'];
        }

        $category->delete();

        return ['type' => 'success', 'message' => 'Kategorie usunieto.'];
    }
}
