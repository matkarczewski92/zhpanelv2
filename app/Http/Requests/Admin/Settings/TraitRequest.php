<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class TraitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gene_ids' => ['sometimes', 'array'],
            'gene_ids.*' => ['integer', 'exists:animal_genotype_category,id'],
        ];
    }
}
