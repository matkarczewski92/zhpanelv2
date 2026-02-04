<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class LitterShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'planning_source' => $this->normalizeText($this->input('planning_source')),
            'planning_connection_date' => $this->normalizeText($this->input('planning_connection_date')),
            'planning_laying_date' => $this->normalizeText($this->input('planning_laying_date')),
            'planning_hatching_date' => $this->normalizeText($this->input('planning_hatching_date')),
        ]);
    }

    public function rules(): array
    {
        return [
            'planning_source' => ['nullable', 'in:connection,laying,hatching'],
            'planning_connection_date' => ['nullable', 'date'],
            'planning_laying_date' => ['nullable', 'date'],
            'planning_hatching_date' => ['nullable', 'date'],
            'edit_offspring' => ['nullable', 'boolean'],
            'open_gallery' => ['nullable', 'boolean'],
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
