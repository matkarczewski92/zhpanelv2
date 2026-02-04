<?php

namespace Tests\Unit\Application\Feeds;

use App\Application\Feeds\Queries\GetFeedDeliveryDraftQuery;
use App\Models\Feed;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GetFeedDeliveryDraftQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_calculates_receipt_total_and_available_feeds(): void
    {
        $first = Feed::query()->create([
            'name' => 'Small mouse',
            'feeding_interval' => 7,
            'amount' => 1,
            'last_price' => 1.00,
        ]);

        $second = Feed::query()->create([
            'name' => 'Big rat',
            'feeding_interval' => 14,
            'amount' => 2,
            'last_price' => 2.00,
        ]);

        session()->put('panel.feeds.delivery.receipt', [
            (string) $first->id => [
                'feed_id' => $first->id,
                'amount' => 2,
                'value' => 8.50,
            ],
        ]);

        $viewModel = app(GetFeedDeliveryDraftQuery::class)->handle();

        $this->assertSame(8.50, $viewModel->total);
        $this->assertSame('8,50 zl', $viewModel->totalLabel);
        $this->assertTrue($viewModel->hasItems);
        $this->assertCount(1, $viewModel->receiptRows);
        $this->assertSame($first->name, $viewModel->receiptRows[0]['name']);
        $this->assertCount(1, $viewModel->availableFeeds);
        $this->assertSame($second->id, $viewModel->availableFeeds[0]['id']);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('feeds');

        Schema::create('feeds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('feeding_interval')->default(0);
            $table->integer('amount')->default(0);
            $table->double('last_price', 8, 2)->default(0);
            $table->timestamps();
        });
    }
}
