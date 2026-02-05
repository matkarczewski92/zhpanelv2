<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class LitterPlanningIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tab' => $this->normalizeText($this->input('tab')),
            'season' => $this->normalizeInt($this->input('season')),
            'expected_genes' => $this->normalizeText($this->input('expected_genes')),
            'strict_visual_only' => $this->normalizeBoolean($this->input('strict_visual_only')),
            'roadmap_expected_genes' => $this->normalizeText($this->input('roadmap_expected_genes')),
            'roadmap_generations' => $this->normalizeInt($this->input('roadmap_generations')),
            'roadmap_id' => $this->normalizeInt($this->input('roadmap_id')),
            'roadmap_open_id' => $this->normalizeInt($this->input('roadmap_open_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'in:planning,plans,offspring,connections,roadmap,roadmaps,roadmap-keepers'],
            'season' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'expected_genes' => ['nullable', 'string', 'max:500'],
            'strict_visual_only' => ['nullable', 'boolean'],
            'roadmap_expected_genes' => ['nullable', 'string', 'max:500'],
            'roadmap_generations' => ['nullable', 'integer', 'min:2', 'max:5'],
            'roadmap_id' => ['nullable', 'integer', 'exists:litter_roadmaps,id'],
            'roadmap_open_id' => ['nullable', 'integer', 'exists:litter_roadmaps,id'],
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
