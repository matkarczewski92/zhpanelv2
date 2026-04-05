<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminLitterLabelsExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'litter_ids' => $this->normalizeIds($this->input('litter_ids', [])),
        ]);
    }

    public function rules(): array
    {
        return [
            'litter_ids' => ['required', 'array', 'min:1'],
            'litter_ids.*' => ['required', 'integer', 'exists:litters,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'litter_ids.required' => 'Wybierz co najmniej jeden miot.',
            'litter_ids.array' => 'Lista miotow jest niepoprawna.',
            'litter_ids.min' => 'Wybierz co najmniej jeden miot.',
            'litter_ids.*.exists' => 'Jeden z wybranych miotow nie istnieje.',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIds(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->flatMap(function (mixed $item): array {
                if (is_string($item)) {
                    return array_filter(array_map('trim', explode(',', $item)));
                }

                return (array) $item;
            })
            ->map(fn (mixed $item): int => (int) $item)
            ->filter(fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
