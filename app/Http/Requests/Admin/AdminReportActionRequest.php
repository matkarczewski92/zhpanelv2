<?php

namespace App\Http\Requests\Admin;

use App\Application\Admin\Services\BuildAdminReportDataService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminReportActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report_type' => trim((string) $this->input('report_type')),
            'date_from' => $this->filled('date_from') ? trim((string) $this->input('date_from')) : null,
            'date_to' => $this->filled('date_to') ? trim((string) $this->input('date_to')) : null,
            'report_date' => $this->filled('report_date') ? trim((string) $this->input('report_date')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'report_type' => [
                'required',
                'string',
                Rule::in([
                    BuildAdminReportDataService::TYPE_SALES,
                    BuildAdminReportDataService::TYPE_DAILY_ENTERED_DATA,
                ]),
            ],
            'date_from' => [
                Rule::requiredIf(fn (): bool => $this->input('report_type') === BuildAdminReportDataService::TYPE_SALES),
                'nullable',
                'date',
            ],
            'date_to' => [
                Rule::requiredIf(fn (): bool => $this->input('report_type') === BuildAdminReportDataService::TYPE_SALES),
                'nullable',
                'date',
            ],
            'report_date' => [
                Rule::requiredIf(fn (): bool => $this->input('report_type') === BuildAdminReportDataService::TYPE_DAILY_ENTERED_DATA),
                'nullable',
                'date',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('report_type') !== BuildAdminReportDataService::TYPE_SALES) {
                return;
            }

            $from = $this->input('date_from');
            $to = $this->input('date_to');

            if ($from && $to && $from > $to) {
                $validator->errors()->add('date_to', 'Data koncowa musi byc pozniejsza lub rowna dacie poczatkowej.');
            }
        });
    }
}
