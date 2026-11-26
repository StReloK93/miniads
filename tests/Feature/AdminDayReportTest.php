<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function dayAd(User $user, Category $category, string $createdAt, bool $bot = false): Product
{
    $product = Product::withoutEvents(fn () => Product::create([
        'title' => 'Telefon ' . $createdAt,
        'description' => 'Yaxshi holatda',
        'price' => 100,
        'category_id' => $category->id,
        'user_id' => $user->id,
        'phone' => '+998901234567',
        'expires_at' => now()->addDays(10),
    ]));
    $product->forceFill(['is_bot' => $bot, 'created_at' => $createdAt])->saveQuietly();

    return $product;
}

test('admin sees any single day with the previous day for comparison', function () {
    Sanctum::actingAs($admin = User::create(['name' => 'Admin', 'role' => 'admin']));
    $category = Category::create(['name' => 'Telefonlar']);
    $day = now()->subDays(3)->setTime(14, 30);

    dayAd($admin, $category, $day->toDateTimeString());
    dayAd($admin, $category, $day->copy()->setTime(15, 0)->toDateTimeString(), bot: true);
    dayAd($admin, $category, $day->copy()->subDay()->toDateTimeString());
    DB::table('search_logs')->insert(['query' => 'iphone', 'results_count' => 0, 'created_at' => $day]);

    $response = $this->getJson('/api/admin/day?date=' . $day->toDateString())->assertOk();

    expect($response->json('metrics.ads'))->toBe(2)
        ->and($response->json('metrics.ads_ai'))->toBe(1)
        ->and($response->json('previous.ads'))->toBe(1)
        ->and($response->json('metrics.searches_empty'))->toBe(1)
        ->and($response->json('hours.14'))->toBe(1)
        ->and($response->json('trend'))->toHaveCount(14)
        ->and($response->json('trend.13.date'))->toBe($day->toDateString())
        ->and($response->json('categories.0.name'))->toBe('Telefonlar')
        ->and($response->json('searches.0.empty'))->toBeTrue();

    // "Hammasi" havolasi: shu kungi e'lonlar ro'yxati
    expect($this->getJson('/api/admin/products?date=' . $day->toDateString())->json('total'))->toBe(2);

    $this->getJson('/api/admin/day?date=' . now()->addDay()->toDateString())->assertUnprocessable();
});

test('activity and search stats accept a single date', function () {
    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));
    $date = now()->subDays(5)->toDateString();
    DB::table('activity_stats')->insert([
        ['date' => $date, 'event' => 'visitors', 'ref' => null, 'hits' => 9, 'users' => 7],
        ['date' => now()->toDateString(), 'event' => 'visitors', 'ref' => null, 'hits' => 3, 'users' => 2],
    ]);

    $this->getJson("/api/admin/activity?date={$date}")
        ->assertOk()
        ->assertJsonPath('date', $date)
        ->assertJsonPath('summary.visitors', 7);

    $this->getJson("/api/admin/search-stats?date={$date}")->assertOk()->assertJsonPath('total', 0);
});

test('admin products list shows calls, telegram clicks and favorites per ad', function () {
    Sanctum::actingAs($admin = User::create(['name' => 'Admin', 'role' => 'admin']));
    $product = dayAd($admin, Category::create(['name' => 'Telefonlar']), now()->toDateTimeString());
    DB::table('activity_stats')->insert([
        ['date' => now()->subDays(2)->toDateString(), 'event' => 'contact_phone', 'ref' => (string) $product->id, 'hits' => 3, 'users' => 2],
        ['date' => now()->toDateString(), 'event' => 'contact_phone', 'ref' => (string) $product->id, 'hits' => 1, 'users' => 1],
        ['date' => now()->toDateString(), 'event' => 'contact_telegram', 'ref' => (string) $product->id, 'hits' => 2, 'users' => 2],
    ]);

    $row = collect($this->getJson('/api/admin/products')->assertOk()->json('data'))->firstWhere('id', $product->id);

    expect($row['phone_clicks'])->toBe(4)
        ->and($row['telegram_clicks'])->toBe(2)
        ->and($row['favorites_count'])->toBe(0);
});
