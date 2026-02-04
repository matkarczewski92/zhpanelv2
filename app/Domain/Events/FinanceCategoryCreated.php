<?php

namespace App\Domain\Events;

use App\Models\FinanceCategory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinanceCategoryCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly FinanceCategory $category)
    {
    }
}
