<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLitterRequest extends FormRequest
{
    protected $errorBag = 'litterCreate';

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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((int) $this->input('parent_male') === (int) $this->input('parent_female')) {
                    $validator->errors()->add('parent_female', 'Samiec i samica musza byc rozne.');
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

