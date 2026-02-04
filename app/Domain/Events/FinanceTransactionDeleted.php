<?php

namespace App\Domain\Events;

use App\Models\Finance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinanceTransactionDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Finance $transaction)
    {
    }
}
