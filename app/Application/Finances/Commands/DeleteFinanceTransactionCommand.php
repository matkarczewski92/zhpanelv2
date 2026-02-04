<?php

namespace App\Application\Finances\Commands;

use App\Domain\Events\FinanceTransactionDeleted;
use App\Models\Finance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteFinanceTransactionCommand
{
    public function handle(int $id): void
    {
        $transaction = Finance::query()->find($id);
        if (!$transaction) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($transaction): void {
            $transaction->delete();

            DB::afterCommit(static function () use ($transaction): void {
                event(new FinanceTransactionDeleted($transaction));
            });
        });
    }
}
