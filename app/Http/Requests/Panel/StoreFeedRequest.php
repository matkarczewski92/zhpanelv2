<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'feeding_interval' => ['required', 'integer', 'min:0'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
