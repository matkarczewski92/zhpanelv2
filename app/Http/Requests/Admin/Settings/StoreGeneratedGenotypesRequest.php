<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneratedGenotypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selected_animal_ids' => ['sometimes', 'array'],
            'selected_animal_ids.*' => ['integer', 'exists:animals,id'],
        ];
    }
}
