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
            'strict_visual_only' => $this->has('strict_visual_only')
                ? $this->normalizeBoolean($this->input('strict_visual_only'))
                : null,
            'connections_only_above_250' => $this->has('connections_only_above_250')
                ? $this->normalizeBoolean($this->input('connections_only_above_250'))
                : null,
            'roadmap_expected_genes' => $this->normalizeText($this->input('roadmap_expected_genes')),
            'roadmap_priority_mode' => $this->normalizeRoadmapPriorityMode($this->input('roadmap_priority_mode')),
            'roadmap_excluded_root_pairs' => $this->normalizeText($this->input('roadmap_excluded_root_pairs')),
            'roadmap_generations' => $this->normalizeInt($this->input('roadmap_generations')),
            'roadmap_generation_one_only_above_250' => $this->has('roadmap_generation_one_only_above_250')
                ? $this->normalizeBoolean($this->input('roadmap_generation_one_only_above_250'))
                : null,
            'roadmap_id' => $this->normalizeInt($this->input('roadmap_id')),
            'roadmap_open_id' => $this->normalizeInt($this->input('roadmap_open_id')),
            'offspring_sort' => $this->normalizeOffspringSort($this->input('offspring_sort')),
            'offspring_direction' => $this->normalizeDirection($this->input('offspring_direction')),
            'offspring_summary_sort' => $this->normalizeOffspringSummarySort($this->input('offspring_summary_sort')),
            'offspring_summary_direction' => $this->normalizeDirection($this->input('offspring_summary_direction')),
            'possible_connections_genes' => $this->normalizeText($this->input('possible_connections_genes')),
            'possible_connections_page' => $this->normalizeInt($this->input('possible_connections_page')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'in:planning,plans,offspring,possible-connections,connections,roadmap,roadmaps,roadmap-keepers'],
            'season' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'expected_genes' => ['nullable', 'string', 'max:500'],
            'strict_visual_only' => ['nullable', 'boolean'],
            'connections_only_above_250' => ['nullable', 'boolean'],
            'roadmap_expected_genes' => ['nullable', 'string', 'max:500'],
            'roadmap_priority_mode' => ['nullable', 'in:fastest,highest_probability'],
            'roadmap_excluded_root_pairs' => ['nullable', 'string', 'max:500'],
            'roadmap_generations' => ['nullable', 'integer', 'min:2', 'max:5'],
            'roadmap_generation_one_only_above_250' => ['nullable', 'boolean'],
            'roadmap_id' => ['nullable', 'integer', 'exists:litter_roadmaps,id'],
            'roadmap_open_id' => ['nullable', 'integer', 'exists:litter_roadmaps,id'],
            'offspring_sort' => ['nullable', 'in:litter_id,litter_code,season,traits_name,traits,traits_count,percentage'],
            'offspring_direction' => ['nullable', 'in:asc,desc'],
            'offspring_summary_sort' => ['nullable', 'in:morph_name,percentage_sum,avg_eggs_to_incubation,numeric_count,litters_count,grouped_rows'],
            'offspring_summary_direction' => ['nullable', 'in:asc,desc'],
            'possible_connections_genes' => ['nullable', 'string', 'max:500'],
            'possible_connections_page' => ['nullable', 'integer', 'min:1'],
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

    private function normalizeOffspringSort(mixed $value): ?string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = strtolower($normalized);
        return in_array($normalized, ['litter_id', 'litter_code', 'season', 'traits_name', 'traits', 'traits_count', 'percentage'], true)
            ? $normalized
            : null;
    }

    private function normalizeDirection(mixed $value): ?string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = strtolower($normalized);

        return in_array($normalized, ['asc', 'desc'], true)
            ? $normalized
            : null;
    }

    private function normalizeOffspringSummarySort(mixed $value): ?string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = strtolower($normalized);
        return in_array($normalized, ['morph_name', 'percentage_sum', 'avg_eggs_to_incubation', 'numeric_count', 'litters_count', 'grouped_rows'], true)
            ? $normalized
            : null;
    }
}
