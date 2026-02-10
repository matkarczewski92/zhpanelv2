<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\WinteringStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WinteringStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scheme' => trim((string) $this->input('scheme', '')),
            'title' => trim((string) $this->input('title', '')),
            'order' => (int) $this->input('order', 0),
            'duration' => (int) $this->input('duration', 0),
        ]);
    }

    public function rules(): array
    {
        $stage = $this->route('stage');
        $stageId = $stage instanceof WinteringStage ? (int) $stage->id : null;

        return [
            'scheme' => ['required', 'string', 'max:255'],
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('winterings_stage', 'order')
                    ->where(fn ($query) => $query->where('scheme', (string) $this->input('scheme')))
                    ->ignore($stageId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'order.unique' => 'Dla tego schematu istnieje juz etap z taka kolejnoscia.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.settings.index', ['tab' => 'winter']);
    }
}
