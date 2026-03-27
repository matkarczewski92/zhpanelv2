<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQrSessionSummaryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $entries = collect($this->input('entries', []))
            ->map(function ($entry): array {
                $entry = is_array($entry) ? $entry : [];

                return [
                    'mode' => trim((string) ($entry['mode'] ?? '')),
                    'animal_id' => isset($entry['animal_id']) ? (int) $entry['animal_id'] : null,
                    'occurred_at' => isset($entry['occurred_at']) ? trim((string) $entry['occurred_at']) : null,
                    'feed_type' => isset($entry['feed_type']) ? trim((string) $entry['feed_type']) : null,
                    'quantity' => isset($entry['quantity']) ? (int) $entry['quantity'] : null,
                    'value' => isset($entry['value']) ? (float) $entry['value'] : null,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'session_started_at' => trim((string) $this->input('session_started_at')),
            'entries' => $entries,
        ]);
    }

    public function rules(): array
    {
        return [
            'session_started_at' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1', 'max:1000'],
            'entries.*.mode' => ['required', 'string', Rule::in(['feeding', 'weight', 'molt'])],
            'entries.*.animal_id' => ['required', 'integer', 'min:1'],
            'entries.*.occurred_at' => ['required', 'date'],
            'entries.*.feed_type' => ['nullable', 'string', 'max:255'],
            'entries.*.quantity' => ['nullable', 'integer', 'min:1'],
            'entries.*.value' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('entries', []) as $index => $entry) {
                $mode = $entry['mode'] ?? null;

                if ($mode === 'feeding' && (empty($entry['feed_type']) || empty($entry['quantity']))) {
                    $validator->errors()->add("entries.$index.feed_type", 'Karmienie wymaga typu karmy i ilosci.');
                }

                if ($mode === 'weight' && !isset($entry['value'])) {
                    $validator->errors()->add("entries.$index.value", 'Wazenie wymaga podania wartosci.');
                }
            }
        });
    }
}
