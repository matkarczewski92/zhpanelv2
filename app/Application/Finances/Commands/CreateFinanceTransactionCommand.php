<?php

namespace App\Application\Finances\Commands;

use App\Domain\Events\FinanceTransactionCreated;
use App\Models\Finance;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateFinanceTransactionCommand
{
    public function handle(array $data): Finance
    {
        $payload = Arr::only($data, [
            'finances_category_id',
            'amount',
            'title',
            'feed_id',
            'animal_id',
            'type',
        ]);

        return DB::transaction(function () use ($payload, $data): Finance {
            $transaction = new Finance($payload);

            $date = Carbon::parse($data['transaction_date'])->startOfDay();
            $transaction->created_at = $date;
            $transaction->updated_at = $date;
            $transaction->save();

            DB::afterCommit(static function () use ($transaction): void {
                event(new FinanceTransactionCreated($transaction));
            });

            return $transaction;
        });
    }
}
