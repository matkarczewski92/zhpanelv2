<?php

namespace Tests\Feature\Panel;

use App\Models\Feed;
use App\Models\FinanceCategory;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeedDeliveryFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->withoutMiddleware(Authenticate::class);
        $this->prepareSchema();
    }

    public function test_delivery_item_validation_rejects_invalid_payload(): void
    {
        $feed = Feed::query()->create([
            'name' => 'Test feed',
            'feeding_interval' => 7,
            'amount' => 5,
            'last_price' => 2.50,
        ]);

        $response = $this->post(route('panel.feeds.delivery.items.store'), [
            'feed_id' => '',
            'amount' => 0,
            'value' => -1,
        ]);

        $response->assertSessionHasErrorsIn('feedDelivery', ['feed_id', 'amount', 'value']);

        $response = $this->post(route('panel.feeds.delivery.items.store'), [
            'feed_id' => $feed->id,
            'amount' => 'not-a-number',
            'value' => '12,2,3',
        ]);

        $response->assertSessionHasErrorsIn('feedDelivery', ['amount', 'value']);
    }

    public function test_adds_delivery_item_to_session_cart(): void
    {
        $feed = Feed::query()->create([
            'name' => 'Mouse XL',
            'feeding_interval' => 10,
            'amount' => 2,
            'last_price' => 1.00,
        ]);

        $response = $this->post(route('panel.feeds.delivery.items.store'), [
            'feed_id' => $feed->id,
            'amount' => '4',
            'value' => '10,50',
        ]);

        $response->assertRedirect(route('panel.feeds.index'));
        $response->assertSessionHas("panel.feeds.delivery.receipt.{$feed->id}.feed_id", $feed->id);
        $response->assertSessionHas("panel.feeds.delivery.receipt.{$feed->id}.amount", 4);
        $response->assertSessionHas("panel.feeds.delivery.receipt.{$feed->id}.value", function ($value): bool {
            return abs((float) $value - 10.5) < 0.0001;
        });
    }

    public function test_commits_delivery_and_increases_stock(): void
    {
        $feed = Feed::query()->create([
            'name' => 'Rat',
            'feeding_interval' => 5,
            'amount' => 10,
            'last_price' => 2.00,
        ]);

        $category = FinanceCategory::query()->create([
            'name' => 'Karma',
        ]);

        $response = $this->withSession([
            'panel.feeds.delivery.receipt' => [
                (string) $feed->id => [
                    'feed_id' => $feed->id,
                    'amount' => 3,
                    'value' => 15.00,
                ],
            ],
        ])->post(route('panel.feeds.delivery.commit'));

        $response->assertRedirect(route('panel.feeds.index'));
        $response->assertSessionMissing('panel.feeds.delivery.receipt');

        $feed->refresh();
        $this->assertSame(13, (int) $feed->amount);
        $this->assertSame(5.00, (float) $feed->last_price);

        $this->assertDatabaseHas('finances', [
            'finances_category_id' => $category->id,
            'feed_id' => $feed->id,
            'amount' => 15.00,
            'title' => 'Zakup karmy: Rat - 3 szt',
            'type' => 'c',
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('finances');
        Schema::dropIfExists('finances_category');
        Schema::dropIfExists('feeds');

        Schema::create('feeds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('feeding_interval')->default(0);
            $table->integer('amount')->default(0);
            $table->double('last_price', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('finances_category', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('finances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('finances_category_id');
            $table->double('amount', 8, 2)->nullable();
            $table->string('title');
            $table->unsignedBigInteger('feed_id')->nullable();
            $table->unsignedBigInteger('animal_id')->nullable();
            $table->string('type');
            $table->timestamps();
        });
    }
}
