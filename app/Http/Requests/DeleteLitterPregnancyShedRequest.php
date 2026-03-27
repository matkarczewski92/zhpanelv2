<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteLitterPregnancyShedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pregnancy_season' => ['nullable', 'string', 'max:50'],
        ];
    }
}
