<?php

use App\Jobs\NotifyPriceDrop;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function priceDropSetup(): array
{
    $owner = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Avto', 'is_page' => true]);
    $product = Product::create(['title' => 'Cobalt 2020', 'price' => 14000, 'price_type_id' => null, 'category_id' => $category->id, 'user_id' => $owner->id]);

    return [$owner, $category, $product];
}

test('lowering the price queues a notification, raising it does not', function () {
    Queue::fake();
    [$owner, $category, $product] = priceDropSetup();
    Sanctum::actingAs($owner);
    $payload = ['title' => 'Cobalt 2020', 'category_id' => $category->id, 'phone' => '931234567'];

    $this->postJson("/api/products/{$product->id}", [...$payload, 'price' => 15000])->assertOk();
    Queue::assertNotPushed(NotifyPriceDrop::class);

    $this->postJson("/api/products/{$product->id}", [...$payload, 'price' => 13500])->assertOk();
    Queue::assertPushed(NotifyPriceDrop::class, fn ($job) => $job->productId === $product->id && $job->oldPrice === 15000);
});

test('only fans who allow it get the message, once a day', function () {
    config(['telegraph.bots' => []]);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
    [, , $product] = priceDropSetup();

    $fan = User::create(['name' => 'Xaridor', 'role' => 'user', 'telegram_user_id' => 101]);
    $fan->forceFill(['bot_can_message' => true])->save();
    $muted = User::create(['name' => 'Jim', 'role' => 'user', 'telegram_user_id' => 102, 'notify_price_drop' => false]);
    $muted->forceFill(['bot_can_message' => true])->save();
    Favorite::create(['user_id' => $fan->id, 'product_id' => $product->id]);
    Favorite::create(['user_id' => $muted->id, 'product_id' => $product->id]);

    $product->update(['price' => 13500]);
    (new NotifyPriceDrop($product->id, 14000))->handle(app(\App\Services\TelegramNotifier::class));
    (new NotifyPriceDrop($product->id, 14000))->handle(app(\App\Services\TelegramNotifier::class));

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => (string) $request->data()['chat_id'] === '101'
        && str_contains($request->data()['text'] ?? $request->data()['caption'] ?? '', '14 000'));
});

test('clearing the price or setting 0 makes the ad negotiable', function () {
    Queue::fake();
    [$owner, $category, $product] = priceDropSetup();
    Sanctum::actingAs($owner);
    $payload = ['title' => 'Cobalt 2020', 'category_id' => $category->id, 'phone' => '931234567'];

    // FormData null maydonni yubormaydi — narx kelmasa ham tozalanadi
    $this->post("/api/products/{$product->id}", $payload, ['Accept' => 'application/json'])->assertOk();
    expect($product->fresh()->price)->toBeNull();

    $product->update(['price' => 14000]);
    $this->post("/api/products/{$product->id}", [...$payload, 'price' => '0'], ['Accept' => 'application/json'])->assertOk();
    expect($product->fresh()->price)->toBeNull();
});
