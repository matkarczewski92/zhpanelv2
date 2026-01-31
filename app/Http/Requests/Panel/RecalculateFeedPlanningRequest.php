<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class RecalculateFeedPlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.feed_id' => ['required', 'integer', 'exists:feeds,id'],
            'items.*.order_qty' => ['required', 'integer', 'min:0'],
        ];
    }
}
