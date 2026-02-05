<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class StoreLitterRoadmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'roadmap_expected_genes' => $this->normalizeText($this->input('roadmap_expected_genes')),
            'roadmap_generations' => $this->normalizeInt($this->input('roadmap_generations')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'roadmap_expected_genes' => ['required', 'string', 'max:500'],
            'roadmap_generations' => ['nullable', 'integer', 'min:2', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwe roadmapy.',
            'name.min' => 'Nazwa roadmapy musi miec co najmniej 3 znaki.',
            'roadmap_expected_genes.required' => 'Podaj docelowe geny/traity.',
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

    private function normalizeInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}

