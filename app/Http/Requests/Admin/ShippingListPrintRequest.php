<?php

namespace App\Http\Requests\Admin;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShippingListPrintRequest extends FormRequest
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $ids = (array) $this->input('animal_ids', []);
                $validCount = Animal::query()
                    ->whereIn('id', $ids)
                    ->whereIn('animal_category_id', [1, 2, 4])
                    ->count();

                if ($validCount !== count($ids)) {
                    $validator->errors()->add(
                        'animal_ids',
                        'Lista przewozowa pozwala drukowac tylko zwierzeta z kategorii 1, 2 lub 4.'
                    );
                }
            },
        ];
    }
}

