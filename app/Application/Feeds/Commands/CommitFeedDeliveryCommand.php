<?php

namespace App\Application\Feeds\Commands;

use App\Application\Feeds\Services\FeedDeliveryDraftService;
use App\Application\Feeds\ViewModels\FeedDeliveryCommitResult;
use App\Domain\Events\FeedDeliveryCommitted;
use App\Models\Feed;
use App\Models\Finance;
use App\Models\FinanceCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommitFeedDeliveryCommand
{
    public function __construct(private readonly FeedDeliveryDraftService $draftService)
    {
    }

    public function handle(): FeedDeliveryCommitResult
    {
        $items = array_values($this->draftService->all());
        if ($items === []) {
            throw ValidationException::withMessages([
                'delivery' => 'Rachunek jest pusty.',
            ]);
        }

        return DB::transaction(function () use ($items): FeedDeliveryCommitResult {
            $financeCategoryId = $this->resolveFinanceCategoryId();
            $savedItems = [];
            $totalValue = 0.0;

            foreach ($items as $item) {
                $feedId = (int) $item['feed_id'];
                $amount = (int) $item['amount'];
                $value = round((float) $item['value'], 2);

                $feed = Feed::query()->lockForUpdate()->find($feedId);
                if (!$feed) {
                    throw ValidationException::withMessages([
                        'delivery' => "Pozycja karmy #{$feedId} nie istnieje.",
                    ]);
                }

                if ($amount <= 0 || $value < 0) {
                    throw ValidationException::withMessages([
                        'delivery' => 'Rachunek zawiera niepoprawne dane.',
                    ]);
                }

                $unitPrice = round($value / $amount, 2);

                $feed->amount = (int) $feed->amount + $amount;
                $feed->last_price = $unitPrice;
                $feed->save();

                Finance::query()->create([
                    'finances_category_id' => $financeCategoryId,
                    'amount' => $value,
                    'title' => sprintf('Zakup karmy: %s - %d szt', $feed->name, $amount),
                    'feed_id' => $feed->id,
                    'type' => 'c',
                ]);

                $savedItems[] = [
                    'feed_id' => $feed->id,
                    'feed_name' => $feed->name,
                    'amount' => $amount,
                    'value' => $value,
                ];
                $totalValue += $value;
            }

            $this->draftService->clear();

            $result = new FeedDeliveryCommitResult(
                itemsCount: count($savedItems),
                totalValue: $totalValue,
            );

            DB::afterCommit(function () use ($savedItems, $totalValue): void {
                event(new FeedDeliveryCommitted($savedItems, $totalValue));
            });

            return $result;
        });
    }

    private function resolveFinanceCategoryId(): int
    {
        $categoryId = (int) FinanceCategory::query()
            ->where('name', 'Karma')
            ->value('id');

        if ($categoryId > 0) {
            return $categoryId;
        }

        $fallbackId = (int) FinanceCategory::query()->orderBy('id')->value('id');
        if ($fallbackId > 0) {
            return $fallbackId;
        }

        throw ValidationException::withMessages([
            'delivery' => 'Brak kategorii finansowej dla zakupu karmy.',
        ]);
    }
}
