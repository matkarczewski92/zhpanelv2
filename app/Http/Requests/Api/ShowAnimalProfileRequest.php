<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ShowAnimalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'secret_tag' => $this->route('secret_tag'),
        ]);
    }

    public function rules(): array
    {
        return [
            'secret_tag' => ['required', 'string', 'min:5', 'max:10', 'alpha_num'],
        ];
    }
}
