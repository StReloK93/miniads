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

test('admin activation queues a channel repost', function () {
    Queue::fake();
    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => User::firstOrCreate(['name' => 'Sotuvchi'], ['role' => 'user'])->id, 'expires_at' => now()->subDay()]);

    $this->patchJson("/api/admin/products/{$product->id}/status", ['status' => 'active'])->assertOk();

    Queue::assertPushed(SyncProductToTelegram::class, fn ($job) => $job->productId === $product->id && $job->action === SyncProductToTelegram::REPOST);
});

test('repost sends a new channel message and deletes the old one', function () {
    config(['services.telegram.channels' => ['-100111'], 'telegraph.bots' => [], 'telegraph.bot_token' => 'test-token']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 88]])]);

    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => User::firstOrCreate(['name' => 'Sotuvchi'], ['role' => 'user'])->id]);
    ProductTelegramId::create(['product_id' => $product->id, 'channel_id' => '-100111', 'message_id' => 5]);

    app(TelegramProductService::class)->repost($product);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage'));
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/deleteMessage') && (int) $request->data()['message_id'] === 5);
    expect(ProductTelegramId::where('product_id', $product->id)->value('message_id'))->toBe(88);
});

test('a channel post Telegram refuses to delete is marked as removed', function () {
    config(['services.telegram.channels' => ['-100111'], 'telegraph.bots' => [], 'telegraph.bot_token' => 'test-token']);
    Http::fake([
        'api.telegram.org/*/deleteMessage' => Http::response(['ok' => false, 'error_code' => 400, 'description' => "Bad Request: message can't be deleted"], 400),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 5]]),
    ]);

    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => User::firstOrCreate(['name' => 'Sotuvchi'], ['role' => 'user'])->id]);
    ProductTelegramId::create(['product_id' => $product->id, 'channel_id' => '-100111', 'message_id' => 5]);

    app(TelegramProductService::class)->deleteFromChannels($product);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/editMessageCaption') && ! isset($request->data()['reply_markup']));
    expect(ProductTelegramId::where('product_id', $product->id)->exists())->toBeFalse();
});
