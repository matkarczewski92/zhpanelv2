<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PriceListPrintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('animal_ids', []);
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $normalized = collect($ids)
            ->flatMap(function ($value) {
                if (is_string($value)) {
                    return array_filter(explode(',', $value));
                }

                return (array) $value;
            })
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge(['animal_ids' => $normalized]);
    }

    public function rules(): array
    {
        return [
            'animal_ids' => ['required', 'array', 'min:1'],
            'animal_ids.*' => ['required', 'integer', 'exists:animals,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_ids.required' => 'Wybierz co najmniej jedno zwierze.',
            'animal_ids.array' => 'Lista zwierzat jest niepoprawna.',
            'animal_ids.min' => 'Wybierz co najmniej jedno zwierze.',
            'animal_ids.*.exists' => 'Jedno z wybranych zwierzat nie istnieje.',
        ];
    }
}

