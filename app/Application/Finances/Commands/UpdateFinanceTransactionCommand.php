<?php

namespace App\Application\Finances\Commands;

use App\Domain\Events\FinanceTransactionUpdated;
use App\Models\Finance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateFinanceTransactionCommand
{
    public function handle(array $data): Finance
    {
        $transaction = Finance::query()->find($data['id']);
        if (!$transaction) {
            throw new ModelNotFoundException();
        }

        $payload = Arr::only($data, [
            'finances_category_id',
            'amount',
            'title',
            'feed_id',
            'animal_id',
            'type',
        ]);

        return DB::transaction(function () use ($transaction, $payload, $data): Finance {
            $transaction->fill($payload);
            $transaction->created_at = Carbon::parse($data['transaction_date'])->startOfDay();
            $transaction->save();

            DB::afterCommit(static function () use ($transaction): void {
                event(new FinanceTransactionUpdated($transaction));
            });

            return $transaction;
        });
    }
}
