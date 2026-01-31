<?php

namespace App\Http\Requests;

use App\Domain\Shared\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'second_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sex' => ['required', 'integer', Rule::in(array_keys(Sex::options()))],
            'date_of_birth' => ['required', 'date'],
            'animal_type_id' => ['required', 'exists:animal_type,id'],
            'litter_id' => ['sometimes', 'nullable', 'exists:litters,id'],
            'feed_id' => ['sometimes', 'nullable', 'exists:feeds,id'],
            'feed_interval' => ['sometimes', 'nullable', 'integer'],
            'animal_category_id' => ['required', 'exists:animal_category,id'],
            'public_profile' => ['sometimes', 'nullable', 'boolean'],
            'public_profile_tag' => ['sometimes', 'nullable', 'string', 'max:255'],
            'web_gallery' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
