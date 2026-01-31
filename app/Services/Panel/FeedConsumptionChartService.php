<?php

namespace App\Services\Panel;

use App\Models\AnimalFeeding;
use App\Models\Feed;
use Illuminate\Support\Collection;

class FeedConsumptionChartService
{
    /**
        * @return array{labels: array<int,string>, datasets: array<int,array<string,mixed>>}
        */
    public function getChartData(int $year): array
    {
        $rows = AnimalFeeding::query()
            ->selectRaw('feed_id, MONTH(created_at) as month, SUM(amount) as total')
            ->whereYear('created_at', $year)
            ->groupBy('feed_id', 'month')
            ->get();

        $feedIds = $rows->pluck('feed_id')->unique()->values();
        $feeds = Feed::whereIn('id', $feedIds)->pluck('name', 'id');

        $labels = range(1, 12);
        $palette = $this->palette();

        $datasets = $feedIds->map(function ($feedId, $index) use ($feeds, $rows, $labels, $palette) {
            $data = array_fill(1, 12, 0);

            $rows->where('feed_id', $feedId)
                ->each(function ($row) use (&$data) {
                    $month = (int) $row->month;
                    $data[$month] = (float) $row->total;
                });

            $color = $palette[$index % count($palette)];

            return [
                'label' => $feeds[$feedId] ?? "Karma #{$feedId}",
                'data' => array_values($data),
                'borderColor' => $color,
                'backgroundColor' => $this->withAlpha($color, 0.25),
                'fill' => true,
                'tension' => 0.2,
                'pointRadius' => 2,
            ];
        })->values()->all();

        return [
            'labels' => array_map('strval', $labels),
            'datasets' => $datasets,
        ];
    }

    /**
        * @return array<int,int>
        */
    public function getAvailableYears(): array
    {
        $years = AnimalFeeding::query()
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->values()
            ->all();

        $current = now()->year;
        if (!in_array($current, $years, true)) {
            array_unshift($years, $current);
        }

        return $years;
    }

    /**
        * @return array<int,string>
        */
    private function palette(): array
    {
        return [
            '#8dd3c7', '#ffffb3', '#bebada', '#fb8072', '#80b1d3', '#fdb462',
            '#b3de69', '#fccde5', '#d9d9d9', '#bc80bd', '#ccebc5', '#ffed6f',
        ];
    }

    private function withAlpha(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return sprintf('rgba(%d, %d, %d, %.2f)', $r, $g, $b, $alpha);
    }
}
