<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncAnimalColorGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('color_group_ids', []);

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $this->merge([
            'color_group_ids' => array_values(array_unique(array_filter(
                array_map(fn ($id) => is_numeric($id) ? (int) $id : null, $ids),
                fn ($id) => $id !== null && $id > 0
            ))),
        ]);
    }

    public function rules(): array
    {
        return [
            'color_group_ids' => ['nullable', 'array'],
            'color_group_ids.*' => ['integer', 'exists:color_groups,id'],
        ];
    }
}

