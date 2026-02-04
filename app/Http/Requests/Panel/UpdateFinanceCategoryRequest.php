<?php

namespace App\Http\Requests\Panel;

use App\Models\FinanceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceCategoryRequest extends FormRequest
{
    protected $errorBag = 'financeCategoryUpdate';

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
        $categoryId = $category instanceof FinanceCategory ? $category->id : (int) $category;

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
