<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('old archived and long-expired ads are deleted, recent and permanent ones stay', function () {
    $user = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);
    $make = fn (array $attributes) => Product::create(['title' => 'E\'lon', 'category_id' => $category->id, 'user_id' => $user->id, ...$attributes]);

    $oldArchive = $make([]);
    $oldArchive->delete();
    Product::withTrashed()->whereKey($oldArchive->id)->update(['deleted_at' => now()->subDays(91)]);
    $freshArchive = $make([]);
    $freshArchive->delete();

    $oldBot = $make(['is_bot' => true, 'expires_at' => now()->subDays(31)]);
    $oldUser = $make(['expires_at' => now()->subDays(181)]);
    $recentlyExpiredUser = $make(['expires_at' => now()->subDays(40)]);
    $permanent = $make(['expires_at' => null]);

    $this->artisan('products:prune')->assertSuccessful();

    $left = Product::withTrashed()->pluck('id');
    expect($left)->not->toContain($oldArchive->id, $oldBot->id, $oldUser->id)
        ->and($left)->toContain($freshArchive->id, $recentlyExpiredUser->id, $permanent->id);
});
