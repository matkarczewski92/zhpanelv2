<?php

namespace App\Http\Requests\Panel;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OffspringPredictionRequest extends FormRequest
{
    protected $errorBag = 'littersPlanningPredict';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'female_id' => $this->normalizeInt($this->input('female_id')),
            'male_id' => $this->normalizeInt($this->input('male_id')),
            'view' => trim((string) $this->input('view', 'summary')),
        ]);
    }

    public function rules(): array
    {
        return [
            'female_id' => ['required', 'integer', 'exists:animals,id'],
            'male_id' => ['required', 'integer', 'exists:animals,id'],
            'view' => ['nullable', 'in:summary,full'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $femaleId = (int) $this->input('female_id');
            $maleId = (int) $this->input('male_id');

            $animals = Animal::query()
                ->whereIn('id', [$femaleId, $maleId])
                ->get(['id', 'sex', 'animal_type_id'])
                ->keyBy('id');

            $female = $animals->get($femaleId);
            $male = $animals->get($maleId);
            if (!$female || !$male) {
                return;
            }

            if ((int) $female->sex !== 3) {
                $validator->errors()->add('female_id', 'Wybierz samice.');
            }

            if ((int) $male->sex !== 2) {
                $validator->errors()->add('male_id', 'Wybierz samca.');
            }

            if ((int) $female->animal_type_id !== (int) $male->animal_type_id) {
                $validator->errors()->add('male_id', 'Samiec i samica musza miec ten sam typ.');
            }
        });
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
