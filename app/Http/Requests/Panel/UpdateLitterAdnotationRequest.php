<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLitterAdnotationRequest extends FormRequest
{
    protected $errorBag = 'litterAdnotation';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('adnotation');
        $this->merge([
            'adnotation' => $value === null ? null : trim((string) $value),
        ]);
    }

    public function rules(): array
    {
        return [
            'adnotation' => ['nullable', 'string'],
        ];
    }
}

