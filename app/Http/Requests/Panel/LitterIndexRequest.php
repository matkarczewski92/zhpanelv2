<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class LitterIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->normalizeText($this->input('q')),
            'season' => $this->normalizeInteger($this->input('season')),
            'status' => $this->normalizeText($this->input('status')),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'season' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:waiting_connection,waiting_laying,incubation,feeding,closed'],
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

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}

