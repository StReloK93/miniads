<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('owner can pause an ad as sold; re-activating clears it; admin sees the count', function () {
    $owner = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $this->postJson("/api/products/{$product->id}/deactivate", ['sold' => true])->assertOk();
    expect($product->fresh()->sold_at)->not->toBeNull()
        ->and(Product::active()->whereKey($product->id)->exists())->toBeFalse();

    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));
    $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('stats.sold_products_month', 1);

    Sanctum::actingAs($owner);
    $this->postJson("/api/products/{$product->id}/activate")->assertOk();
    expect($product->fresh()->sold_at)->toBeNull();
});

test('pausing for another reason is not counted as sold', function () {
    $owner = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $this->postJson("/api/products/{$product->id}/deactivate")->assertOk();
    expect($product->fresh()->sold_at)->toBeNull();
});

test('re-activated ad shows the re-activation time, not the original posting time', function () {
    $owner = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => $owner->id, 'expires_at' => now()->subDay()]);
    $product->forceFill(['published_at' => now()->subDays(20)])->save();
    Sanctum::actingAs($owner);

    $this->postJson("/api/products/{$product->id}/activate")->assertOk();

    $published = $this->getJson("/api/products/{$product->id}")->assertOk()->json('published_at');
    expect($published)->toContain('T') // ISO — iOS Safari ham o'qiydi
        ->and(\Illuminate\Support\Carbon::parse($published)->diffInMinutes(now(), true))->toBeLessThan(2);
});

test('a new ad shows the time it was posted', function () {
    $owner = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $product = Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => $owner->id]);

    $published = $this->getJson("/api/products/{$product->id}")->assertOk()->json('published_at');
    expect(\Illuminate\Support\Carbon::parse($published)->diffInMinutes(now(), true))->toBeLessThan(2);
});
