<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLitterOffspringBatchRequest extends FormRequest
{
    protected $errorBag = 'litterOffspringBatch';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = (array) $this->input('rows', []);
        foreach ($rows as $index => $row) {
            $rows[$index]['weight'] = $this->normalizeDecimal($row['weight'] ?? null);
        }

        $this->merge(['rows' => $rows]);
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['required', 'integer', 'exists:animals,id'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.sex' => ['required', 'integer', 'in:1,2,3'],
            'rows.*.weight' => ['nullable', 'numeric', 'min:0'],
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

        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }
}

