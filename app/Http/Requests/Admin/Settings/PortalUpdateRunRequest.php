<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class PortalUpdateRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'run_migrate' => ['sometimes', 'boolean'],
            'run_build' => ['sometimes', 'boolean'],
        ];
    }
}
