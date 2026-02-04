<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class LitterPlanningIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tab' => $this->normalizeText($this->input('tab')),
            'season' => $this->normalizeInt($this->input('season')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'in:planning,plans,offspring'],
            'season' => ['nullable', 'integer', 'min:2000', 'max:2100'],
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

    private function normalizeInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}

