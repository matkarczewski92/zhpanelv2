<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class ResolveQrAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payload' => trim((string) $this->input('payload')),
        ]);
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'string', 'max:500'],
        ];
    }
}
