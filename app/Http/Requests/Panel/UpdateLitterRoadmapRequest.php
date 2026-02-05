<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLitterRoadmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'return_tab' => $this->normalizeText($this->input('return_tab')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'return_tab' => ['nullable', 'in:roadmap,roadmaps'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwe roadmapy.',
            'name.min' => 'Nazwa roadmapy musi miec co najmniej 3 znaki.',
        ];
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
