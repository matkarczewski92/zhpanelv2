<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class StoreQrMoltRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payload' => trim((string) $this->input('payload')),
            'confirm_duplicate' => $this->boolean('confirm_duplicate'),
        ]);
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'string', 'max:500'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }
}
