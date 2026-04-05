<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminLitterLabelsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $categoryIds = $this->normalizeIds($this->input('category_ids', [1, 2]));

        if ($categoryIds === []) {
            $categoryIds = [1, 2];
        }

        $this->merge([
            'category_ids' => $categoryIds,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'in:1,2,3,4'],
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
