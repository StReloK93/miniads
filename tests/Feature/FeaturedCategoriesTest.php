<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function featuredAdmin(): User
{
    $admin = User::create(['name' => 'Administrator', 'role' => 'admin']);
    Sanctum::actingAs($admin);

    return $admin;
}

test('featured endpoint lists only marked categories in admin order', function () {
    Category::create(['name' => 'Oddiy']);
    Category::create(['name' => "Yo'lovchilar", 'is_featured' => true, 'featured_order' => 2]);
    Category::create(['name' => 'Kafe-Restoranlar', 'is_featured' => true, 'featured_order' => 1]);

    $this->getJson('/api/categories/featured')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Kafe-Restoranlar')
        ->assertJsonPath('1.name', "Yo'lovchilar");
});

test('ads in a permanent category never expire, even after re-activation', function () {
    $admin = featuredAdmin();
    $category = Category::create(['name' => 'Kafe-Restoranlar', 'is_page' => true, 'is_permanent' => true]);
    $product = Product::create([
        'title' => 'Oqtepa kafe',
        'category_id' => $category->id,
        'user_id' => $admin->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->patchJson("/api/admin/products/{$product->id}/status", ['status' => 'active'])->assertOk();

    $product->refresh();
    expect($product->getRawOriginal('expires_at'))->toBeNull()
        ->and($product->days['permanent'])->toBeTrue()
        ->and(Product::active()->whereKey($product->id)->exists())->toBeTrue();
});

test('one-day category gives ads a single day', function () {
    $category = Category::create(['name' => 'Naqd-Plastik', 'listing_duration_days' => 1]);

    expect($category->listingExpiresAt()->isSameDay(now()->addDay()))->toBeTrue();
});

test('turning "permanent" on and off updates active ads of the category', function () {
    $admin = featuredAdmin();
    $category = Category::create(['name' => 'Kafe-Restoranlar', 'is_page' => true, 'listing_duration_days' => 7]);
    $active = Product::create([
        'title' => 'Faol', 'category_id' => $category->id, 'user_id' => $admin->id, 'expires_at' => now()->addDays(3),
    ]);
    $expired = Product::create([
        'title' => 'Tugagan', 'category_id' => $category->id, 'user_id' => $admin->id, 'expires_at' => now()->subDay(),
    ]);

    $this->postJson("/api/categories/{$category->id}", ['name' => 'Kafe-Restoranlar', 'is_page' => true, 'is_permanent' => true])
        ->assertOk();

    expect($active->fresh()->isPermanent())->toBeTrue()
        // Muddati o'tgani tirilmaydi
        ->and($expired->fresh()->isPermanent())->toBeFalse();

    $this->postJson("/api/categories/{$category->id}", ['name' => 'Kafe-Restoranlar', 'is_page' => true, 'is_permanent' => false])
        ->assertOk();

    $active->refresh();
    expect($active->isPermanent())->toBeFalse()
        ->and(Carbon::parse($active->expires_at)->isSameDay(now()->addDays(7)))->toBeTrue();
});

test('saving a category without a duration keeps the current one', function () {
    featuredAdmin();
    $category = Category::create(['name' => 'Naqd-Plastik', 'listing_duration_days' => 1]);

    $this->postJson("/api/categories/{$category->id}", ['name' => 'Naqd-Plastik', 'listing_duration_days' => ''])
        ->assertOk();

    expect($category->fresh()->listing_duration_days)->toBe(1);
});

function cashCategory(): Category
{
    (new Database\Seeders\FeaturedCategorySeeder())->run();

    return Category::where('name', 'Naqd-Plastik')->firstOrFail();
}

test('cash-card ad needs no title: it is built from direction and amount', function () {
    Sanctum::actingAs(User::create(['name' => 'Foydalanuvchi', 'role' => 'user']));
    $category = cashCategory();
    [$direction, $amount] = $category->parameters()->orderBy('category_parameters.sort_order')->pluck('parameters.id');

    $this->postJson('/api/products', [
        'category_id' => $category->id,
        'phone' => '+998901234567',
        'parameters' => [
            ['id' => $direction, 'value' => 'Naqd beraman, plastikka olaman'],
            ['id' => $amount, 'value' => '1500000'],
        ],
    ])->assertCreated()->assertJsonPath('title', "Naqd beraman, plastikka olaman · 1 500 000 so'm");

    $product = Product::latest('id')->first();
    expect($product->title)->toBe("Naqd beraman, plastikka olaman · 1 500 000 so'm")
        ->and(Carbon::parse($product->expires_at)->isSameDay(now()->addDay()))->toBeTrue();
});

test('required parameters must be filled', function () {
    Sanctum::actingAs(User::create(['name' => 'Foydalanuvchi', 'role' => 'user']));
    $category = cashCategory();

    $this->postJson('/api/products', ['category_id' => $category->id, 'phone' => '+998901234567', 'parameters' => []])
        ->assertUnprocessable();
});

test('categories with titles still require one', function () {
    Sanctum::actingAs(User::create(['name' => 'Foydalanuvchi', 'role' => 'user']));
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);

    $this->postJson('/api/products', ['category_id' => $category->id, 'phone' => '+998901234567'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});
