<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class AnimalOfferUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'sold_at' => ['nullable', 'date'],
            'public_profile' => ['nullable', 'boolean'],
            'reserver_name' => ['nullable', 'string', 'max:255'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'reservation_valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
