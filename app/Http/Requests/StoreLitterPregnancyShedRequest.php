<?php

namespace App\Http\Requests;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;

class StoreLitterPregnancyShedRequest extends FormRequest
{
    protected $errorBag = 'pregnancyShed';

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
            'litter_id' => ['required', 'integer', 'exists:litters,id'],
            'shed_date' => ['required', 'date'],
            'pregnancy_season' => ['nullable', 'string', 'max:50'],
        ];
    }
}
