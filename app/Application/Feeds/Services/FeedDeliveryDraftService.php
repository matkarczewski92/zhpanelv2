<?php

namespace App\Application\Feeds\Services;

use Illuminate\Contracts\Session\Session;

class FeedDeliveryDraftService
{
    private const SESSION_KEY = 'panel.feeds.delivery.receipt';

    public function __construct(private readonly Session $session)
    {
    }

    /**
     * @return array<string, array{feed_id:int, amount:int, value:float}>
     */
    public function all(): array
    {
        $rawItems = $this->session->get(self::SESSION_KEY, []);
        if (!is_array($rawItems)) {
            return [];
        }

        $items = [];

        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $feedId = (int) ($item['feed_id'] ?? 0);
            $amount = (int) ($item['amount'] ?? 0);
            $value = (float) ($item['value'] ?? 0);

            if ($feedId <= 0 || $amount <= 0 || $value < 0) {
                continue;
            }

            $items[(string) $feedId] = [
                'feed_id' => $feedId,
                'amount' => $amount,
                'value' => round($value, 2),
            ];
        }

        return $items;
    }

    public function contains(int $feedId): bool
    {
        return array_key_exists((string) $feedId, $this->all());
    }

    public function addItem(int $feedId, int $amount, float $value): void
    {
        $items = $this->all();
        $items[(string) $feedId] = [
            'feed_id' => $feedId,
            'amount' => $amount,
            'value' => round($value, 2),
        ];

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function removeItem(int $feedId): void
    {
        $items = $this->all();
        unset($items[(string) $feedId]);

        if ($items === []) {
            $this->clear();
            return;
        }

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
