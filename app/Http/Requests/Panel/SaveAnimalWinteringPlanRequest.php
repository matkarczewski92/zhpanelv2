<?php

namespace App\Http\Requests\Panel;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;

class SaveAnimalWinteringPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('rows', []);
        $normalizedRows = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $normalizedRows[] = [
                    'wintering_id' => $this->normalizeInteger($row['wintering_id'] ?? null),
                    'stage_id' => $this->normalizeInteger($row['stage_id'] ?? null),
                    'planned_start_date' => $this->normalizeDate($row['planned_start_date'] ?? null),
                    'planned_end_date' => $this->normalizeDate($row['planned_end_date'] ?? null),
                    'start_date' => $this->normalizeDate($row['start_date'] ?? null),
                    'end_date' => $this->normalizeDate($row['end_date'] ?? null),
                    'custom_duration' => $this->normalizeInteger($row['custom_duration'] ?? null),
                ];
            }
        }

        $this->merge([
            'scheme' => trim((string) $this->input('scheme', '')),
            'rows' => $normalizedRows,
        ]);
    }

    public function rules(): array
    {
        return [
            'scheme' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.wintering_id' => ['nullable', 'integer', 'exists:winterings,id'],
            'rows.*.stage_id' => ['required', 'integer', 'exists:winterings_stage,id'],
            'rows.*.planned_start_date' => ['nullable', 'date'],
            'rows.*.planned_end_date' => ['nullable', 'date'],
            'rows.*.start_date' => ['nullable', 'date'],
            'rows.*.end_date' => ['nullable', 'date'],
            'rows.*.custom_duration' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $animal = $this->route('animal');
        $animalId = $animal instanceof Animal ? (int) $animal->id : (int) $animal;

        return route('panel.animals.show', $animalId) . '#wintering';
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
