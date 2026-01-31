<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SystemConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
        ];
    }
}
