<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin api rejects users without the admin role', function () {
    Sanctum::actingAs(User::create([
        'name' => 'Regular user',
        'role' => 'user',
    ]));

    $this->getJson('/api/admin/dashboard')->assertForbidden();
    $this->postJson('/api/categories', ['name' => 'Unauthorized category'])->assertForbidden();
});

test('admin can access dashboard and user management endpoints', function () {
    Sanctum::actingAs(User::create([
        'name' => 'Administrator',
        'role' => 'admin',
    ]));

    $this->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'stats' => ['products', 'active_products', 'expired_products', 'deleted_products', 'users', 'admins', 'categories'],
            'recent_products',
        ]);

    $this->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonPath('data.0.role', 'admin');

    $this->getJson('/api/admin/products')
        ->assertOk()
        ->assertJsonPath('data', []);

    $this->patchJson('/api/admin/users/1/role', ['role' => 'user'])
        ->assertUnprocessable();
});

test('public catalog reads remain available to visitors', function () {
    $this->getJson('/api/categories')->assertOk();
    $this->getJson('/api/districts')->assertOk();
    $this->getJson('/api/price-types')->assertOk();
});

test('admin can activate and archive an advertisement', function () {
    $admin = User::create([
        'name' => 'Administrator',
        'role' => 'admin',
    ]);
    Sanctum::actingAs($admin);

    $category = Category::create(['name' => 'Uy-joy', 'listing_duration_days' => 14]);
    $product = Product::create([
        'title' => 'Sinov e’loni',
        'category_id' => $category->id,
        'user_id' => $admin->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->patchJson("/api/admin/products/{$product->id}/status", ['status' => 'active'])
        ->assertOk();
    expect(\Illuminate\Support\Carbon::parse($product->fresh()->expires_at)->isFuture())->toBeTrue();

    $this->deleteJson("/api/admin/products/{$product->id}")
        ->assertOk();
    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});
