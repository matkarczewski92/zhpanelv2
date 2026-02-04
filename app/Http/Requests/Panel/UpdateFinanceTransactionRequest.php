<?php

namespace App\Http\Requests\Panel;

class UpdateFinanceTransactionRequest extends StoreFinanceTransactionRequest
{
    protected $errorBag = 'financeEdit';
}
