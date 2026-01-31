<?php

namespace App\Http\Requests;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalWeightRequest extends FormRequest
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
            'value' => ['required', 'numeric'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
