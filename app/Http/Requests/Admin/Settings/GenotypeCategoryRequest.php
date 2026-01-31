<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class GenotypeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gene_code' => ['nullable', 'string', 'max:10'],
            'gene_type' => ['nullable', 'string', 'max:10'],
        ];
    }
}
