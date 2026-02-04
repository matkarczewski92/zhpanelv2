<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CommitMassDataRequest extends FormRequest
{
    protected $errorBag = 'massData';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('rows', []);
        $normalizedRows = [];

        if (is_array($rows)) {
            foreach ($rows as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $normalizedRows[$key] = [
                    'animal_id' => $this->normalizeInteger($row['animal_id'] ?? $key),
                    'weight' => $this->normalizeDecimal($row['weight'] ?? null),
                    'feed_id' => $this->normalizeInteger($row['feed_id'] ?? null),
                    'amount' => $this->normalizeInteger($row['amount'] ?? null),
                    'feed_check' => $this->normalizeBoolean($row['feed_check'] ?? null),
                ];
            }
        }

        $this->merge([
            'category_id' => $this->normalizeInteger($this->input('category_id')),
            'rows' => $normalizedRows,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'in:1,2'],
            'transaction_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.animal_id' => ['required', 'integer', 'exists:animals,id'],
            'rows.*.weight' => ['nullable', 'numeric', 'min:0'],
            'rows.*.feed_id' => ['nullable', 'integer', 'exists:feeds,id'],
            'rows.*.amount' => ['nullable', 'integer', 'min:1'],
            'rows.*.feed_check' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Brak sekcji masowych danych.',
            'category_id.in' => 'Niepoprawna sekcja masowych danych.',
            'transaction_date.required' => 'Wybierz date zapisu.',
            'transaction_date.date' => 'Data zapisu jest niepoprawna.',
            'rows.required' => 'Brak danych do zapisania.',
            'rows.*.animal_id.required' => 'Brak identyfikatora zwierzecia.',
            'rows.*.animal_id.exists' => 'Wybrane zwierze nie istnieje.',
            'rows.*.weight.numeric' => 'Waga musi byc liczba.',
            'rows.*.weight.min' => 'Waga nie moze byc ujemna.',
            'rows.*.feed_id.exists' => 'Wybrana karma nie istnieje.',
            'rows.*.amount.integer' => 'Ilosc karmy musi byc liczba calkowita.',
            'rows.*.amount.min' => 'Ilosc karmy musi byc wieksza od zera.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ((array) $this->input('rows', []) as $row) {
                    $feedCheck = (bool) ($row['feed_check'] ?? false);
                    if (!$feedCheck) {
                        continue;
                    }

                    $animalId = (int) ($row['animal_id'] ?? 0);
                    $feedId = (int) ($row['feed_id'] ?? 0);
                    $amount = (int) ($row['amount'] ?? 0);

                    if ($feedId <= 0 || $amount <= 0) {
                        $validator->errors()->add('rows', "Uzupelnij karme i ilosc dla zwierzecia #{$animalId}.");
                        break;
                    }
                }
            },
        ];
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $normalized = $this->normalizeNumberString($value);
        if ($normalized === null || !preg_match('/^-?\d+(?:\.0+)?$/', $normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        $normalized = $this->normalizeNumberString($value);
        if ($normalized === null || !is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }

    private function normalizeNumberString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return $normalized;
    }
}
