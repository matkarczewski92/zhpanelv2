<?php

namespace App\Http\Requests\Panel;

use App\Application\Feeds\Services\FeedDeliveryDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFeedDeliveryItemRequest extends FormRequest
{
    protected $errorBag = 'feedDelivery';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeInteger($this->input('amount')),
            'value' => $this->normalizeDecimal($this->input('value')),
        ]);
    }

    public function rules(): array
    {
        return [
            'feed_id' => ['required', 'integer', 'exists:feeds,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'value' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'feed_id.required' => 'Wybierz karme.',
            'feed_id.exists' => 'Wybrana karma nie istnieje.',
            'amount.required' => 'Podaj ilosc.',
            'amount.integer' => 'Ilosc musi byc liczba calkowita.',
            'amount.min' => 'Ilosc musi byc wieksza od zera.',
            'value.required' => 'Podaj wartosc.',
            'value.numeric' => 'Wartosc musi byc liczba.',
            'value.min' => 'Wartosc nie moze byc ujemna.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $feedId = (int) $this->input('feed_id');
                $draftService = app(FeedDeliveryDraftService::class);

                if ($draftService->contains($feedId)) {
                    $validator->errors()->add('feed_id', 'Ta karma jest juz na rachunku.');
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
