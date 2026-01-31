<?php

namespace App\Http\Requests;

use App\Domain\Shared\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'second_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', 'integer', Rule::in(array_keys(Sex::options()))],
            'date_of_birth' => ['required', 'date'],
            'animal_type_id' => ['nullable', 'exists:animal_type,id'],
            'litter_id' => ['nullable', 'exists:litters,id'],
            'feed_id' => ['nullable', 'exists:feeds,id'],
            'feed_interval' => ['nullable', 'integer'],
            'animal_category_id' => ['nullable', 'exists:animal_category,id'],
            'public_profile' => ['nullable', 'boolean'],
            'public_profile_tag' => ['nullable', 'string', 'max:255'],
            'web_gallery' => ['nullable', 'integer'],
        ];
    }
}
