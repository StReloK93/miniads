<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('old bot ads get 93-444-33-44 phones in the field and the description, cafe prices are removed', function () {
    $user = User::create(['name' => 'Bot', 'role' => 'user']);
    $shop = Category::create(['name' => 'Avto', 'is_page' => true]);
    $cafe = Category::create(['name' => 'Kafe', 'is_page' => true, 'with_price' => false]);
    $row = fn (array $data) => DB::table('products')->insertGetId([
        'title' => 'E\'lon', 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(), ...$data,
    ]);

    $bot = $row(['category_id' => $shop->id, 'is_bot' => true, 'phone' => '998934443344', 'price' => 7000,
        'description' => "Spark sotiladi.\n• Bog'lanish uchun: +99893 444 33 44"]);
    $own = $row(['category_id' => $shop->id, 'is_bot' => false, 'phone' => '@ali_avto', 'description' => 'Tel +99893 444 33 44']);
    $kafe = $row(['category_id' => $cafe->id, 'is_bot' => true, 'phone' => '+998 90 123 45 67', 'price' => 50000]);

    (require database_path('migrations/2026_11_08_120000_normalize_product_phones_and_no_price_categories.php'))->up();

    $get = fn ($id) => DB::table('products')->find($id);
    expect($get($bot)->phone)->toBe('93-444-33-44')
        ->and($get($bot)->description)->toBe("Spark sotiladi.\n• Bog'lanish uchun: 93-444-33-44")
        ->and((int) $get($bot)->price)->toBe(7000)
        ->and($get($own)->phone)->toBe('@ali_avto')
        ->and($get($own)->description)->toBe('Tel +99893 444 33 44')
        ->and($get($kafe)->phone)->toBe('90-123-45-67')
        ->and($get($kafe)->price)->toBeNull();
});
