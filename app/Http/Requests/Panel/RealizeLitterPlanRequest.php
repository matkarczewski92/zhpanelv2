<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class RealizeLitterPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'planned_year' => $this->normalizeInt($this->input('planned_year')),
        ]);
    }

    public function rules(): array
    {
        return [
            'planned_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
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

