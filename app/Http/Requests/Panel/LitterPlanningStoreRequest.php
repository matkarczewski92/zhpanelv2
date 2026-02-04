<?php

namespace App\Http\Requests\Panel;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LitterPlanningStoreRequest extends FormRequest
{
    protected $errorBag = 'litterPlanningStore';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pairsPayload = $this->input('pairs_json', $this->input('pairs', []));
        $pairs = $this->normalizePairs($pairsPayload);

        $this->merge([
            'plan_id' => $this->normalizeInt($this->input('plan_id')),
            'plan_name' => trim((string) $this->input('plan_name')),
            'planned_year' => $this->normalizeYear($this->input('planned_year')),
            'pairs' => $pairs,
        ]);
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['nullable', 'integer', 'exists:litter_plans,id'],
            'plan_name' => ['required', 'string', 'min:3', 'max:255'],
            'planned_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*.female_id' => ['required', 'integer', 'exists:animals,id'],
            'pairs.*.male_id' => ['required', 'integer', 'exists:animals,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_name.required' => 'Podaj nazwe planu.',
            'plan_name.min' => 'Nazwa planu musi miec co najmniej 3 znaki.',
            'planned_year.integer' => 'Rok planu musi byc liczba.',
            'pairs.required' => 'Dodaj przynajmniej jedna pare.',
            'pairs.min' => 'Dodaj przynajmniej jedna pare.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pairs = (array) $this->input('pairs', []);
            $usedKeys = [];

            foreach ($pairs as $index => $pair) {
                $femaleId = (int) ($pair['female_id'] ?? 0);
                $maleId = (int) ($pair['male_id'] ?? 0);

                if ($femaleId > 0 && $femaleId === $maleId) {
                    $validator->errors()->add("pairs.{$index}.male_id", 'Samica i samiec nie moga byc tym samym zwierzeciem.');
                }

                $key = $femaleId . ':' . $maleId;
                if (isset($usedKeys[$key])) {
                    $validator->errors()->add("pairs.{$index}.male_id", 'Ta para zostala dodana wiecej niz raz.');
                }
                $usedKeys[$key] = true;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $animalIds = collect($pairs)
                ->flatMap(fn (array $pair): array => [(int) ($pair['female_id'] ?? 0), (int) ($pair['male_id'] ?? 0)])
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $animals = Animal::query()
                ->whereIn('id', $animalIds)
                ->get(['id', 'sex', 'animal_type_id'])
                ->keyBy('id');

            foreach ($pairs as $index => $pair) {
                $female = $animals->get((int) ($pair['female_id'] ?? 0));
                $male = $animals->get((int) ($pair['male_id'] ?? 0));
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
     * @return array<int, array{female_id:int, male_id:int}>
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

            $pairs[] = [
                'female_id' => $femaleId,
                'male_id' => $maleId,
            ];
        }

        return $pairs;
    }

    private function normalizeInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function normalizeYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeInt($value);
    }
}
