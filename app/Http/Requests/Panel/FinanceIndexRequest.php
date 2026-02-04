<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FinanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount_from' => $this->normalizeDecimal($this->input('amount_from')),
            'amount_to' => $this->normalizeDecimal($this->input('amount_to')),
            'category_id' => $this->normalizeInteger($this->input('category_id')),
            'title' => $this->normalizeText($this->input('title')),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:c,i'],
            'category_id' => ['nullable', 'integer', 'exists:finances_category,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'amount_from' => ['nullable', 'numeric', 'min:0'],
            'amount_to' => ['nullable', 'numeric', 'min:0'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $dateFrom = $this->input('date_from');
                $dateTo = $this->input('date_to');
                $amountFrom = $this->input('amount_from');
                $amountTo = $this->input('amount_to');

                if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                    $validator->errors()->add('date_to', 'Data do musi byc pozniejsza niz data od.');
                }

                if ($amountFrom !== null && $amountTo !== null && (float) $amountFrom > (float) $amountTo) {
                    $validator->errors()->add('amount_to', 'Kwota do musi byc wieksza lub rowna kwocie od.');
                }
            },
        ];
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (!is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
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
