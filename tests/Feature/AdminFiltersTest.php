<?php

use App\Models\Category;
use App\Models\District;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin users list separates one-time and returning visitors', function () {
    $admin = User::create(['name' => 'Administrator', 'role' => 'admin']);
    $once = User::create(['name' => 'Bir marta']);
    $returning = User::create(['name' => 'Qaytuvchi']);
    Sanctum::actingAs($admin);

    DB::table('user_visits')->insert([
        ['user_id' => $once->id, 'date' => now()->subDays(20)->toDateString()],
        ['user_id' => $returning->id, 'date' => now()->subDays(20)->toDateString()],
        ['user_id' => $returning->id, 'date' => now()->subDays(1)->toDateString()],
        ['user_id' => $returning->id, 'date' => now()->toDateString()],
    ]);

    $response = $this->getJson('/api/admin/users')->assertOk();
    // admin (0 kun) ham "bir marta" qatorida
    expect($response->json('visit_summary'))->toMatchArray([
        'total' => 3, 'once' => 2, 'returning' => 1, 'returned_week' => 1,
    ]);

    $this->getJson('/api/admin/users?visits=returning')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $returning->id)
        ->assertJsonPath('data.0.visit_days', 3);

    $ids = collect($this->getJson('/api/admin/users?visits=once')->json('data'))->pluck('id');
    expect($ids->sort()->values()->all())->toBe([$admin->id, $once->id]);

    $this->getJson('/api/admin/users?sort=visits')->assertOk()->assertJsonPath('data.0.id', $returning->id);
});

test('activity aggregation records one visit per user per day', function () {
    $user = User::create(['name' => 'Foydalanuvchi']);
    $date = '2000-01-01';
    $path = storage_path("app/activity/{$date}.log");
    File::ensureDirectoryExists(dirname($path));
    File::put($path, implode("\n", [
        json_encode(['u' => $user->id, 'e' => 'app_open']),
        json_encode(['u' => $user->id, 'e' => 'page', 'r' => 'home']),
        json_encode(['u' => 999999, 'e' => 'app_open']),
        json_encode(['s' => 'guest-session-1', 'e' => 'app_open']),
    ]) . "\n");

    try {
        ActivityLog::aggregate($date);
        ActivityLog::aggregate($date);
    } finally {
        File::delete($path);
    }

    expect(DB::table('user_visits')->where('user_id', $user->id)->count())->toBe(1)
        ->and(DB::table('user_visits')->count())->toBe(1);
});

test('admin products can be filtered by category subtree and district', function () {
    Sanctum::actingAs($admin = User::create(['name' => 'Administrator', 'role' => 'admin']));

    $root = Category::create(['name' => 'Avto']);
    $child = Category::create(['name' => 'Yengil', 'parent_id' => $root->id]);
    $other = Category::create(['name' => 'Uy-joy']);
    $city = District::create(['name' => 'Zarafshon']);

    $make = fn (string $title, int $categoryId, ?int $districtId) => Product::create([
        'title' => $title, 'category_id' => $categoryId, 'district_id' => $districtId,
        'user_id' => $admin->id, 'expires_at' => now()->addDays(5),
    ]);
    $inChild = $make('Nexia', $child->id, $city->id);
    $inRoot = $make('Ehtiyot qism', $root->id, null);
    $make('Kvartira', $other->id, $city->id);

    $ids = fn (string $query) => collect($this->getJson("/api/admin/products?{$query}")->assertOk()->json('data'))
        ->pluck('id')->sort()->values()->all();

    expect($ids("category_id={$root->id}"))->toBe([$inChild->id, $inRoot->id])
        ->and($ids("category_id={$child->id}"))->toBe([$inChild->id])
        ->and($ids('district_id=none'))->toBe([$inRoot->id])
        ->and($ids("category_id={$root->id}&district_id={$city->id}"))->toBe([$inChild->id]);
});
