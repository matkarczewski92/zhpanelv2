<?php

namespace App\Http\Requests;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalGenotypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $animal = $this->route('animal');

        if ($animal) {
            $this->merge([
                'animal_id' => $animal instanceof Animal ? $animal->id : $animal,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'exists:animals,id'],
            'genotype_id' => ['required', 'exists:animal_genotype_category,id'],
            'type' => ['required', Rule::in(['v', 'h', 'p'])],
        ];
    }
}
