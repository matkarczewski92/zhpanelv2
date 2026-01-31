<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class WinteringStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:0'],
        ];
    }
}
