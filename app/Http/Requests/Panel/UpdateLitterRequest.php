<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateLitterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'litter_code' => $this->normalizeText($this->input('litter_code')),
            'season' => $this->normalizeInteger($this->input('season')),
            'parent_male' => $this->normalizeInteger($this->input('parent_male')),
            'parent_female' => $this->normalizeInteger($this->input('parent_female')),
            'laying_eggs_total' => $this->normalizeInteger($this->input('laying_eggs_total')),
            'laying_eggs_ok' => $this->normalizeInteger($this->input('laying_eggs_ok')),
            'hatching_eggs' => $this->normalizeInteger($this->input('hatching_eggs')),
            'adnotation' => $this->normalizeText($this->input('adnotation')),
        ]);
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'integer', 'in:1,2,3,4'],
            'litter_code' => ['required', 'string', 'max:255'],
            'season' => ['nullable', 'integer', 'min:0'],
            'parent_male' => ['required', 'integer', 'exists:animals,id'],
            'parent_female' => ['required', 'integer', 'exists:animals,id'],
            'planned_connection_date' => ['nullable', 'date'],
            'connection_date' => ['nullable', 'date'],
            'laying_date' => ['nullable', 'date'],
            'hatching_date' => ['nullable', 'date'],
            'laying_eggs_total' => ['nullable', 'integer', 'min:0'],
            'laying_eggs_ok' => ['nullable', 'integer', 'min:0'],
            'hatching_eggs' => ['nullable', 'integer', 'min:0'],
            'adnotation' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((int) $this->input('parent_male') === (int) $this->input('parent_female')) {
                    $validator->errors()->add('parent_female', 'Samiec i samica musza byc rozne.');
                }

                $eggsTotal = $this->input('laying_eggs_total');
                $eggsOk = $this->input('laying_eggs_ok');
                $hatchingEggs = $this->input('hatching_eggs');

                if ($eggsTotal !== null && $eggsOk !== null && $eggsOk > $eggsTotal) {
                    $validator->errors()->add('laying_eggs_ok', 'Jaja do inkubacji nie moga byc wieksze niz zniesione.');
                }

                if ($eggsOk !== null && $hatchingEggs !== null && $hatchingEggs > $eggsOk) {
                    $validator->errors()->add('hatching_eggs', 'Wyklute nie moga byc wieksze niz jaja do inkubacji.');
                }
            },
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

