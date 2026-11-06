<?php

use App\Jobs\SyncProductToTelegram;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTelegramId;
use App\Models\User;
use App\Services\TelegramProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('posting an ad queues the channel post instead of waiting for Telegram', function () {
    Queue::fake();
    Sanctum::actingAs(User::create(['name' => 'Sotuvchi', 'role' => 'user']));
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);

    $id = $this->postJson('/api/products', [
        'title' => 'iPhone 13 128GB',
        'category_id' => $category->id,
        'phone' => '931234567',
    ])->assertCreated()->json('id');

    Queue::assertPushed(SyncProductToTelegram::class, fn ($job) => $job->productId === $id && $job->action === SyncProductToTelegram::PUBLISH);
});

test('a retried publish does not post twice to a channel that already has the ad', function () {
    config(['services.telegram.channels' => ['-100111', '-100222'], 'telegraph.bots' => []]);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 77]])]);

    $user = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => $user->id]);
    ProductTelegramId::create(['product_id' => $product->id, 'channel_id' => '-100111', 'message_id' => 5]);

    app(TelegramProductService::class)->publish($product);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => (string) $request->data()['chat_id'] === '-100222');
});
