<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\FinanceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof FinanceCategory ? $category->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finances_category', 'name')->ignore($categoryId),
            ],
        ];
    }
}
