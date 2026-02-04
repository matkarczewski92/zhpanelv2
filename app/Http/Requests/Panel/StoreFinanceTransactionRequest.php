<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinanceTransactionRequest extends FormRequest
{
    protected $errorBag = 'financeCreate';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeDecimal($this->input('amount')),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:c,i'],
            'finances_category_id' => ['required', 'integer', 'exists:finances_category,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'feed_id' => ['nullable', 'integer', 'exists:feeds,id'],
            'animal_id' => ['nullable', 'integer', 'exists:animals,id'],
        ];
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }
}
