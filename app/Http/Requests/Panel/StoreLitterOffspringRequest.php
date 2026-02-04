<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class StoreLitterOffspringRequest extends FormRequest
{
    protected $errorBag = 'litterOffspring';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}

