<?php

namespace App\Services\Panel;

use App\Models\Animal;
use App\Models\Feed;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FeedDemandPlanningService
{
    public function getInitialPlan(): array
    {
        return $this->buildResponse(collect());
    }

    public function recalculate(Collection|array $items): array
    {
        $orders = collect($items)->mapWithKeys(function ($item) {
            $feedId = (int) ($item['feed_id'] ?? 0);
            $qty = max(0, (int) ($item['order_qty'] ?? 0));

            return [$feedId => $qty];
        });

        return $this->buildResponse($orders);
    }

    private function buildResponse(Collection $orders): array
    {
        $leadTimeDays = $this->resolveLeadTimeDays();
        $today = Carbon::today();

        $demandByFeed = $this->demandPerFeed();
        $feeds = $this->feeds();

        $rows = $feeds->map(function (Feed $feed) use ($orders, $demandByFeed, $leadTimeDays, $today): array {
            $demand = (int) ($demandByFeed[$feed->id] ?? 0);
            $orderQty = (int) $orders->get($feed->id, 0);

            return $this->buildRow($feed, $demand, $orderQty, $leadTimeDays, $today);
        })->keyBy('feed_id');

        $totalCost = $rows->sum(fn (array $row) => $row['row_cost']);
        $hasPricedRows = $rows->contains(fn (array $row) => $row['unit_price'] !== null);

        return [
            'rows' => $rows->toArray(),
            'total_cost' => $totalCost,
            'total_cost_label' => $hasPricedRows ? $this->formatCurrency($totalCost) : '—',
            'lead_time_days' => $leadTimeDays,
            'today' => $today->format('Y-m-d'),
        ];
    }

    private function buildRow(Feed $feed, int $demandUnits, int $orderQty, int $leadTimeDays, Carbon $today): array
    {
        $stock = (float) ($feed->amount ?? 0);
        $unitPrice = $feed->last_price !== null ? (float) $feed->last_price : null;
        $orderQty = max(0, $orderQty);

        $dk = $this->calculateDk($stock, $demandUnits);
        $dz = $this->calculateOrderDate($dk, (int) $feed->feeding_interval, $leadTimeDays, $today);

        $newStock = $stock + $orderQty;
        $newDk = $this->calculateDk($newStock, $demandUnits);
        $newDz = $this->calculateOrderDate($newDk, (int) $feed->feeding_interval, $leadTimeDays, $today);

        $rowCost = $unitPrice !== null ? $orderQty * $unitPrice : 0.0;

        return [
            'feed_id' => $feed->id,
            'name' => $feed->name,
            'stock' => $stock,
            'demand_units' => $demandUnits,
            'dk' => $dk,
            'dk_label' => (string) $dk,
            'dz' => $dz ? $dz->format('Y-m-d') : null,
            'dz_label' => $dz ? $dz->format('Y-m-d') : '—',
            'order_qty' => $orderQty,
            'new_dk' => $newDk,
            'new_dk_label' => (string) $newDk,
            'new_dz' => $newDz ? $newDz->format('Y-m-d') : null,
            'new_dz_label' => $newDz ? $newDz->format('Y-m-d') : '—',
            'unit_price' => $unitPrice,
            'unit_price_label' => $unitPrice !== null ? $this->formatCurrency($unitPrice) : '—',
            'row_cost' => $rowCost,
            'row_cost_label' => $unitPrice !== null ? $this->formatCurrency($rowCost) : '—',
        ];
    }

    private function calculateDk(float $stock, int $demandUnits): int
    {
        if ($demandUnits <= 0) {
            return (int) floor($stock);
        }

        return (int) floor($stock / $demandUnits);
    }

    private function calculateOrderDate(int $dk, int $feedingInterval, int $leadTimeDays, Carbon $today): ?Carbon
    {
        if ($feedingInterval <= 0) {
            return null;
        }

        $daysRemaining = $dk * $feedingInterval;
        return $today->copy()->addDays($daysRemaining)->subDays($leadTimeDays);
    }

    private function demandPerFeed(): Collection
    {
        return Animal::query()
            ->selectRaw('feed_id, SUM(CASE WHEN feed_quantity IS NULL OR feed_quantity < 1 THEN 1 ELSE feed_quantity END) as demand_units')
            ->whereNotNull('feed_id')
            ->whereNotIn('animal_category_id', [3, 5]) // exclude categories not used for feeding plans
            ->groupBy('feed_id')
            ->pluck('demand_units', 'feed_id')
            ->map(fn ($value) => (int) $value);
    }

    private function feeds(): Collection
    {
        return Feed::query()
            ->select(['id', 'name', 'amount', 'last_price', 'feeding_interval'])
            ->orderBy('id')
            ->get();
    }

    private function resolveLeadTimeDays(): int
    {
        $value = (int) SystemConfig::where('key', 'feedLeadTime')->value('value');
        return max(0, $value);
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' zł';
    }
}
