<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class DevicesIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'device' => $this->normalizeText($this->input('device')),
            'code' => $this->normalizeText($this->input('code')),
            'state' => $this->normalizeText($this->input('state')),
        ]);
    }

    public function rules(): array
    {
        return [
            'device' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
