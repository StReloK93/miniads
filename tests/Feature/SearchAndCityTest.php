<?php

use App\Models\Category;
use App\Models\District;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::create(['name' => 'Sotuvchi', 'role' => 'user']);
    $this->jobs = Category::create(['name' => 'Ish', 'is_page' => true]);
    $this->cars = Category::create(['name' => 'Avto', 'is_page' => true]);
    $this->uchquduq = District::create(['name' => 'Uchquduq']);
    $this->zarafshon = District::create(['name' => 'Zarafshon']);

    $this->make = fn (string $title, Category $category, ?District $district = null) => Product::create([
        'title' => $title, 'category_id' => $category->id, 'user_id' => $this->seller->id,
        'district_id' => $district?->id, 'expires_at' => now()->addDay(),
    ]);
});

test('a category alone does not satisfy the other words of the query', function () {
    ($this->make)('Haydovchi kerak', $this->jobs);
    ($this->make)('Sotuvchi kerak', $this->jobs);

    $this->getJson('/api/products/custom/search?search=hamshira ish')
        ->assertOk()
        ->assertJsonCount(0, 'products')
        ->assertJsonPath('related.terms', ['ish'])
        ->assertJsonCount(2, 'related.products');

    $this->getJson('/api/products/custom/count?search=hamshira ish')->assertExactJson(['count' => 0]);
});

test('exact matches come first and related ones are only offered when there are few', function () {
    $nurse = ($this->make)('Hamshira kerak', $this->jobs);
    ($this->make)('Haydovchi kerak', $this->jobs);

    $this->getJson('/api/products/custom/search?search=hamshira ish')
        ->assertJsonPath('products.0.id', $nurse->id)
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('related.terms', ['ish']);

    // Bitta so'zli qidiruvda "o'xshash" bo'lmaydi
    $this->getJson('/api/products/custom/search?search=hamshira')->assertJsonPath('related', null);
});

test('only weak words matched gives no related block', function () {
    ($this->make)('Mebel kerak', $this->cars);

    $this->getJson('/api/products/custom/search?search=hamshira kerak')
        ->assertJsonCount(0, 'products')
        ->assertJsonPath('related', null);
});

test('city filter keeps region-wide ads and category counts follow the city', function () {
    ($this->make)('Cobalt', $this->cars, $this->uchquduq);
    ($this->make)('Gentra', $this->cars, $this->zarafshon);
    ($this->make)('Nexia', $this->cars); // butun viloyat

    $this->getJson("/api/categories/{$this->cars->id}/products?city_id={$this->uchquduq->id}")
        ->assertJsonCount(2, 'products');

    $this->getJson("/api/categories/counts?city_id={$this->uchquduq->id}")
        ->assertJsonPath((string) $this->cars->id, 2);
    $this->getJson('/api/categories/counts')->assertJsonPath((string) $this->cars->id, 3);
});

test('category counts are not stale after a new ad is posted', function () {
    $this->getJson('/api/categories/counts')->assertExactJson([]);

    ($this->make)('Cobalt', $this->cars, $this->uchquduq);

    $this->getJson('/api/categories/counts')->assertJsonPath((string) $this->cars->id, 1);
    $this->getJson("/api/categories/counts?city_id={$this->uchquduq->id}")->assertJsonPath((string) $this->cars->id, 1);
});
