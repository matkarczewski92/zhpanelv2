<?php

namespace App\Http\Requests\Panel;

use App\Application\Feeds\Services\FeedDeliveryDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CommitFeedDeliveryRequest extends FormRequest
{
    protected $errorBag = 'feedDeliveryCommit';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $draftService = app(FeedDeliveryDraftService::class);

                if ($draftService->all() === []) {
                    $validator->errors()->add('delivery', 'Rachunek jest pusty.');
                }
            },
        ];
    }
}
