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
            'roadmap_priority_mode' => $this->normalizeRoadmapPriorityMode($this->input('roadmap_priority_mode')),
            'roadmap_excluded_root_pairs' => $this->normalizeText($this->input('roadmap_excluded_root_pairs')),
            'roadmap_generations' => $this->normalizeInt($this->input('roadmap_generations')),
            'strict_visual_only' => $this->has('strict_visual_only')
                ? $this->normalizeBoolean($this->input('strict_visual_only'))
                : null,
            'roadmap_generation_one_only_above_250' => $this->has('roadmap_generation_one_only_above_250')
                ? $this->normalizeBoolean($this->input('roadmap_generation_one_only_above_250'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'roadmap_expected_genes' => ['required', 'string', 'max:500'],
            'roadmap_priority_mode' => ['nullable', 'in:fastest,highest_probability'],
            'roadmap_excluded_root_pairs' => ['nullable', 'string', 'max:500'],
            'roadmap_generations' => ['nullable', 'integer', 'min:2', 'max:5'],
            'strict_visual_only' => ['nullable', 'boolean'],
            'roadmap_generation_one_only_above_250' => ['nullable', 'boolean'],
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

    private function normalizeRoadmapPriorityMode(mixed $value): ?string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = strtolower($normalized);

        return in_array($normalized, ['fastest', 'highest_probability'], true)
            ? $normalized
            : null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
    }
}
