<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function metaProduct(array $attributes = []): Product
{
    $user = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $category = Category::create(['name' => 'Avto', 'is_page' => true]);

    return Product::create(['title' => 'Cobalt 2020 oq', 'description' => "Holati a'lo", 'price' => 13500, 'category_id' => $category->id, 'user_id' => $user->id, ...$attributes]);
}

test('shared ad link has a card with title, price and photo', function () {
    $product = metaProduct();
    ProductImage::create(['product_id' => $product->id, 'src' => 'products/a.webp', 'thumb_src' => 'products/a_thumb.webp']);

    $this->get("/product/{$product->id}")
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Cobalt 2020 oq — Navoiy e&#039;lonlari">', false)
        ->assertSee('13 500', false)
        ->assertSee('storage/products/a.webp', false);
});

test('expired ad link shows only the general site card', function () {
    $product = metaProduct(['expires_at' => now()->subDay()]);

    $this->get("/product/{$product->id}")
        ->assertOk()
        ->assertDontSee('Cobalt')
        ->assertSee('og:site_name', false);
});
