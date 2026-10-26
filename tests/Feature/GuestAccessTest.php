<?php

use App\Models\Category;
use App\Models\Favorite;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $category = Category::create(['name' => 'Avto', 'is_page' => true, 'listing_duration_days' => 30]);
    PriceType::create(['name' => 'UZS', 'type' => "so'm", 'position' => 'right']);

    $this->owner = User::create(['name' => 'Sotuvchi', 'telegram_user_id' => 1]);
    $this->product = Product::create([
        'title' => 'Cobalt 2022',
        'category_id' => $category->id,
        'user_id' => $this->owner->id,
        'price_type_id' => 1,
        'expires_at' => now()->addDays(10),
    ]);
});

test('guests can browse the feed, search and open an ad', function () {
    $this->getJson('/api/products/custom/feed')->assertOk()->assertJsonPath('data.0.id', $this->product->id);
    $this->getJson('/api/products/custom/latest_ten')->assertOk();
    $this->getJson('/api/products/custom/search?search=cobalt')->assertOk()->assertJsonPath('0.id', $this->product->id);
    $this->getJson("/api/products/{$this->product->id}")->assertOk()->assertJsonPath('is_favorite', false);
    $this->getJson("/api/categories/{$this->product->category_id}/products")->assertOk();
});

test('signed-in users are still recognised on public routes', function () {
    $viewer = User::create(['name' => 'Xaridor', 'telegram_user_id' => 2]);
    Favorite::create(['user_id' => $viewer->id, 'product_id' => $this->product->id]);
    $token = $viewer->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/products/{$this->product->id}")
        ->assertOk()
        ->assertJsonPath('is_favorite', true);
});

test('actions that need an account still require login', function () {
    $this->getJson('/api/favorites')->assertUnauthorized();
    $this->postJson('/api/favorites', ['product_id' => $this->product->id])->assertUnauthorized();
    $this->postJson('/api/products', [])->assertUnauthorized();
    $this->getJson('/api/products/custom/my_ads/active')->assertUnauthorized();
    $this->getJson("/api/products/{$this->product->id}/edit")->assertUnauthorized();
    $this->deleteJson("/api/products/{$this->product->id}")->assertUnauthorized();
});
