<?php

use App\Models\Category;
use App\Models\District;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin finds ads by phone in any format and sees duplicates', function () {
    Sanctum::actingAs($admin = User::create(['name' => 'Administrator', 'role' => 'admin']));
    $category = Category::create(['name' => 'Telefonlar']);
    $make = fn (string $title, ?string $phone) => Product::create([
        'title' => $title, 'phone' => $phone, 'category_id' => $category->id,
        'user_id' => $admin->id, 'expires_at' => now()->addDays(5),
    ]);
    $first = $make('iPhone 13', '93-660-09-60');
    $second = $make('iPhone 13 sotiladi', '+998 93 660 09 60');
    $single = $make('Samsung', '90-111-22-33');

    foreach (['936600960', '+998936600960', '93 660 09 60', '998 93-660-09-60'] as $query) {
        $ids = collect($this->getJson('/api/admin/products?search=' . urlencode($query))->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
        expect($ids)->toBe([$first->id, $second->id]);
    }

    $response = $this->getJson('/api/admin/products?duplicates=1')->assertOk();
    expect($response->json('counts.duplicates'))->toBe(2)
        ->and(collect($response->json('data'))->pluck('id')->sort()->values()->all())->toBe([$first->id, $second->id])
        ->and(collect($response->json('data'))->pluck('same_phone_count')->all())->toBe([1, 1]);

    $row = collect($this->getJson('/api/admin/products')->json('data'))->firstWhere('id', $single->id);
    expect($row['same_phone_count'])->toBe(0);
});

test('admin users are split into posters, city-only and idle segments', function () {
    Sanctum::actingAs($admin = User::create(['name' => 'Administrator', 'role' => 'admin']));
    $city = District::create(['name' => 'Zarafshon']);
    $category = Category::create(['name' => 'Avto']);
    $poster = User::create(['name' => 'Sotuvchi', 'active_district_id' => $city->id]);
    $cityOnly = User::create(['name' => 'Shahar tanlagan', 'active_district_id' => $city->id]);
    Product::create(['title' => 'Nexia', 'category_id' => $category->id, 'user_id' => $poster->id])->delete();

    $ids = fn (string $segment) => collect($this->getJson("/api/admin/users?segment={$segment}")->assertOk()->json('data'))
        ->pluck('id')->sort()->values()->all();

    expect($ids('posters'))->toBe([$poster->id]) // arxivdagi e'lon ham hisobga olinadi
        ->and($ids('city_only'))->toBe([$cityOnly->id])
        ->and($ids('idle'))->toBe([$admin->id])
        ->and($this->getJson('/api/admin/users')->json('segment_counts'))
        ->toMatchArray(['all' => 3, 'posters' => 1, 'city_only' => 1, 'idle' => 1]);
});

test('admin sees a user journey for a day', function () {
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));
    $user = User::create(['name' => 'Foydalanuvchi']);
    $category = Category::create(['name' => 'Avto']);
    $date = '2000-01-02';
    $path = storage_path("app/activity/{$date}.log");
    File::ensureDirectoryExists(dirname($path));
    File::put($path, implode("\n", [
        json_encode(['u' => $user->id, 'e' => 'page', 'r' => 'create-product', 't' => 946800100]),
        json_encode(['u' => $user->id, 'e' => 'ad_failed', 'r' => 'price,images', 't' => 946800200]),
        json_encode(['u' => $user->id, 'e' => 'category_open', 'r' => (string) $category->id, 't' => 946800050]),
        json_encode(['u' => 999, 'e' => 'app_open', 't' => 946800000]),
    ]) . "\n");

    try {
        $events = $this->getJson("/api/admin/users/{$user->id}/journey?date={$date}")->assertOk()->json('events');
    } finally {
        File::delete($path);
    }

    // Vaqt bo'yicha tartiblangan, boshqa foydalanuvchi yozuvlari yo'q, kategoriya nomi bilan
    expect(collect($events)->pluck('e')->all())->toBe(['category_open', 'page', 'ad_failed'])
        ->and($events[0]['label'])->toBe('Avto')
        ->and($events[2]['r'])->toBe('price,images');
});

test('extending an expiring ad adds the remaining time instead of shortening it', function () {
    $owner = User::create(['name' => 'Sotuvchi']);
    $category = Category::create(['name' => 'Valyuta', 'listing_duration_days' => 1]);
    $product = Product::create([
        'title' => 'Naqd → Plastik', 'category_id' => $category->id, 'user_id' => $owner->id,
        'expires_at' => now()->addDays(2),
    ]);
    Sanctum::actingAs($owner);

    $this->postJson("/api/products/{$product->id}/activate")->assertOk();
    $expires = Carbon::parse($product->fresh()->getRawOriginal('expires_at'));
    expect(now()->diffInHours($expires))->toBeGreaterThan(71)->toBeLessThan(73); // 2 kun qolgan + 1 kun

    // Muddati tugagan e'lon — kategoriya muddati hozirdan
    $product->forceFill(['expires_at' => now()->subDay()])->save();
    $this->postJson("/api/products/{$product->id}/activate")->assertOk();
    $expires = Carbon::parse($product->fresh()->getRawOriginal('expires_at'));
    expect(now()->diffInHours($expires))->toBeGreaterThan(23)->toBeLessThan(25);
});

test('dashboard splits daily ads into people and AI and counts users the bot can message', function () {
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));
    $category = Category::create(['name' => 'Avto']);
    $owner = User::create(['name' => 'Sotuvchi']);
    $owner->forceFill(['bot_can_message' => true])->save();
    User::create(['name' => 'Botsiz']);
    Product::create(['title' => 'Nexia', 'category_id' => $category->id, 'user_id' => $owner->id]);
    Product::create(['title' => 'Cobalt', 'category_id' => $category->id, 'user_id' => $owner->id, 'is_bot' => true]);
    Product::create(['title' => 'Spark', 'category_id' => $category->id, 'user_id' => $owner->id, 'is_bot' => true]);

    $response = $this->getJson('/api/admin/dashboard')->assertOk();
    expect($response->json('daily_ads.13'))->toMatchArray(['count' => 3, 'people' => 1, 'ai' => 2])
        ->and($response->json('stats.bot_reachable'))->toBe(1);

    $this->getJson('/api/admin/users?segment=bot')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $owner->id);
});
