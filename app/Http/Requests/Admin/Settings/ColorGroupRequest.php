<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\ColorGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ColorGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'sort_order' => $this->normalizeInt($this->input('sort_order', 0)),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $group = $this->route('colorGroup');
        $groupId = $group instanceof ColorGroup ? $group->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('color_groups', 'name')->ignore($groupId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function normalizeInt(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return (int) $value;
    }
}

