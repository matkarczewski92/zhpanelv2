<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EwelinkDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nameRules = ['string', 'max:255'];
        if ($this->isMethod('post')) {
            array_unshift($nameRules, 'nullable');
        } else {
            array_unshift($nameRules, 'required');
        }

        return [
            'device_id' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('ewelink_devices', 'device_id')->ignore($this->route('device')),
            ],
            'name' => $nameRules,
            'description' => ['nullable', 'string'],
            'device_type' => ['required', Rule::in(['switch', 'thermostat', 'thermostat_hygrostat'])],
        ];
    }
}
