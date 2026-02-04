<?php

namespace App\Http\Requests\Panel;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LitterPlanningPairsRequest extends FormRequest
{
    protected $errorBag = 'litterPlanningPairs';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pairsPayload = $this->input('pairs_json', $this->input('pairs', []));

        $this->merge([
            'female_id' => $this->normalizeInt($this->input('female_id')),
            'pairs' => $this->normalizePairs($pairsPayload),
        ]);
    }

    public function rules(): array
    {
        return [
            'female_id' => ['nullable', 'integer', 'exists:animals,id'],
            'pairs' => ['nullable', 'array'],
            'pairs.*.female_id' => ['required', 'integer', 'exists:animals,id'],
            'pairs.*.male_id' => ['required', 'integer', 'exists:animals,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pairs = (array) $this->input('pairs', []);
            if (empty($pairs)) {
                return;
            }

            $animalIds = collect($pairs)
                ->flatMap(fn (array $pair): array => [(int) $pair['female_id'], (int) $pair['male_id']])
                ->unique()
                ->values();

            $animals = Animal::query()
                ->whereIn('id', $animalIds)
                ->get(['id', 'sex', 'animal_type_id'])
                ->keyBy('id');

            foreach ($pairs as $index => $pair) {
                $female = $animals->get((int) $pair['female_id']);
                $male = $animals->get((int) $pair['male_id']);
                if (!$female || !$male) {
                    continue;
                }

                if ((int) $female->sex !== 3) {
                    $validator->errors()->add("pairs.{$index}.female_id", 'Wybierz samice.');
                }
                if ((int) $male->sex !== 2) {
                    $validator->errors()->add("pairs.{$index}.male_id", 'Wybierz samca.');
                }
                if ((int) $female->animal_type_id !== (int) $male->animal_type_id) {
                    $validator->errors()->add("pairs.{$index}.male_id", 'Samiec i samica musza miec ten sam typ.');
                }
            }
        });
    }

    /**
     * @return array<int, array{female_id:int,male_id:int}>
     */
    private function normalizePairs(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $pairs = [];
        foreach ($value as $pair) {
            if (!is_array($pair)) {
                continue;
            }

            $femaleId = $this->normalizeInt($pair['female_id'] ?? null);
            $maleId = $this->normalizeInt($pair['male_id'] ?? null);
            if (!$femaleId || !$maleId) {
                continue;
            }

            $pairs[] = ['female_id' => $femaleId, 'male_id' => $maleId];
        }

        return collect($pairs)
            ->unique(fn (array $pair): string => $pair['female_id'] . ':' . $pair['male_id'])
            ->values()
            ->all();
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

