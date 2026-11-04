<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('filter count shows how many active ads are left in a category', function () {
    $user = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $cars = Category::create(['name' => 'Avto', 'is_page' => true]);
    $other = Category::create(['name' => 'Boshqa', 'is_page' => true]);

    $make = fn (Category $category, ?int $price, $expires) => Product::create([
        'title' => "E'lon", 'category_id' => $category->id, 'user_id' => $user->id,
        'price' => $price, 'expires_at' => $expires,
    ]);
    $make($cars, 1000, now()->addDay());
    $make($cars, 5000, now()->addDay());
    $make($cars, 9000, now()->subDay()); // muddati o'tgan — sanalmaydi
    $make($other, 1000, now()->addDay()); // boshqa bo'lim

    $this->getJson("/api/products/custom/count?category_id={$cars->id}")->assertOk()->assertExactJson(['count' => 2]);
    $this->getJson("/api/products/custom/count?category_id={$cars->id}&price_from=2000")->assertExactJson(['count' => 1]);
});
