<?php

use App\Models\District;
use App\Models\PriceType;
use App\Models\User;
use App\Models\Vitrina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    PriceType::create(['name' => 'UZS', 'type' => "so'm", 'position' => 'right']);
    $this->city = District::create(['name' => 'Uchquduq']);
    $this->otherCity = District::create(['name' => 'Zarafshon']);
    $this->owner = User::create(['name' => 'Rustam', 'username' => 'rustam', 'role' => 'user']);
    $this->admin = User::create(['name' => 'Admin', 'role' => 'admin']);
});

function createVitrina($test, array $data = []): Vitrina
{
    $test->actingAs($test->owner)->postJson('/api/my-vitrina', [
        'name' => 'Mebel Lux', 'type' => 'shop', 'district_id' => $test->city->id,
        'phone' => '+998 93 555 12 34', 'telegram' => 'https://t.me/mebel_lux', ...$data,
    ])->assertCreated();

    return Vitrina::firstOrFail();
}

test('a new vitrina waits for approval and is hidden from others until then', function () {
    $vitrina = createVitrina($this);

    expect($vitrina->status)->toBe('pending')
        ->and($vitrina->slug)->toBe('mebel-lux')
        ->and($vitrina->phone)->toBe('93-555-12-34')
        ->and($vitrina->telegram)->toBe('mebel_lux');

    $this->app['auth']->forgetGuards();
    $this->getJson('/api/vitrinas')->assertExactJson([]);
    $this->getJson('/api/vitrinas/mebel-lux')->assertNotFound();

    // Egasi o'zinikini ko'radi
    $this->actingAs($this->owner)->getJson('/api/vitrinas/mebel-lux')->assertOk()->assertJsonPath('is_owner', true);

    $this->actingAs($this->admin)->patchJson("/api/admin/vitrinas/{$vitrina->id}", ['status' => 'approved'])->assertOk();

    $this->app['auth']->forgetGuards();
    $this->getJson('/api/vitrinas')->assertJsonCount(1)->assertJsonPath('0.slug', 'mebel-lux');
});

test('only admins moderate vitrinas', function () {
    $vitrina = createVitrina($this);

    $this->actingAs($this->owner)->patchJson("/api/admin/vitrinas/{$vitrina->id}", ['status' => 'approved'])->assertForbidden();
});

test('catalog follows the city: own city and region-wide vitrinas only', function () {
    createVitrina($this);
    Vitrina::query()->update(['status' => 'approved']);

    $other = User::create(['name' => 'Boshqa', 'role' => 'user']);
    $this->actingAs($other)->postJson('/api/my-vitrina', ['name' => 'Zarafshon Osh', 'type' => 'menu', 'district_id' => $this->otherCity->id]);
    $region = User::create(['name' => 'Viloyat', 'role' => 'user']);
    $this->actingAs($region)->postJson('/api/my-vitrina', ['name' => 'Viloyat Usta', 'type' => 'portfolio']);
    Vitrina::query()->update(['status' => 'approved']);

    $this->getJson("/api/vitrinas?city_id={$this->city->id}")->assertJsonCount(2);
    $this->getJson('/api/vitrinas?type=menu')->assertJsonCount(1)->assertJsonPath('0.name', 'Zarafshon Osh');
});

test('items keep a price only when the mode needs one', function () {
    createVitrina($this);

    $this->postJson('/api/my-vitrina/items', ['title' => 'Divan', 'price_mode' => 'fixed'])->assertUnprocessable();

    $this->postJson('/api/my-vitrina/items', ['title' => 'Oshxona', 'price_mode' => 'from', 'price' => 6500000, 'unit' => 'metr'])
        ->assertCreated()
        ->assertJsonPath('price', 6500000)
        ->assertJsonPath('price_type_id', 1);

    $this->postJson('/api/my-vitrina/items', ['title' => "Ta'mir ishi", 'price_mode' => 'none', 'price' => 100])
        ->assertCreated()
        ->assertJsonPath('price', null)
        ->assertJsonPath('price_type_id', null);
});

test('sections belong to their vitrina and deleting one keeps its items', function () {
    createVitrina($this);
    $section = $this->postJson('/api/my-vitrina/sections', ['name' => 'Divanlar'])->assertCreated()->json();
    $item = $this->postJson('/api/my-vitrina/items', [
        'title' => 'Divan', 'price_mode' => 'negotiable', 'vitrina_section_id' => $section['id'],
    ])->assertCreated()->json();

    // Boshqa odamning vitrinasi bo'limiga mahsulot qo'shib bo'lmaydi
    $stranger = User::create(['name' => 'Begona', 'role' => 'user']);
    $this->actingAs($stranger)->postJson('/api/my-vitrina', ['name' => 'Begona', 'type' => 'shop']);
    $this->postJson('/api/my-vitrina/items', ['title' => 'X', 'price_mode' => 'none', 'vitrina_section_id' => $section['id']])
        ->assertUnprocessable();
    $this->deleteJson("/api/my-vitrina/items/{$item['id']}")->assertNotFound();

    $this->actingAs($this->owner)->deleteJson("/api/my-vitrina/sections/{$section['id']}")->assertNoContent();
    $this->getJson('/api/my-vitrina')->assertJsonPath('vitrina.items.0.vitrina_section_id', null);
});

test('item photos: kept ones stay, removed ones are deleted from disk', function () {
    createVitrina($this);

    $item = $this->post('/api/my-vitrina/items', [
        'title' => 'Divan', 'price_mode' => 'none',
        'images' => [UploadedFile::fake()->image('a.jpg', 200, 200), UploadedFile::fake()->image('b.jpg', 200, 200)],
    ], ['Accept' => 'application/json'])->assertCreated()->json();

    [$first, $second] = $item['images'];
    expect(File::exists(public_path("storage/{$first}")))->toBeTrue();

    $this->post("/api/my-vitrina/items/{$item['id']}", [
        'title' => 'Divan', 'price_mode' => 'none', 'keep_images' => [$second, 'vitrinas/items/../../secret.webp'],
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('images', [$second]);

    expect(File::exists(public_path("storage/{$first}")))->toBeFalse();

    $this->deleteJson("/api/my-vitrina/items/{$item['id']}")->assertNoContent();
    expect(File::exists(public_path("storage/{$second}")))->toBeFalse();
});

test('a rejected vitrina goes back to review after the owner edits it', function () {
    $vitrina = createVitrina($this);
    $this->actingAs($this->admin)->patchJson("/api/admin/vitrinas/{$vitrina->id}", ['status' => 'rejected', 'reject_reason' => 'Rasm yomon'])
        ->assertJsonPath('reject_reason', 'Rasm yomon');

    $this->actingAs($this->owner)->postJson('/api/my-vitrina', ['name' => 'Mebel Lux', 'type' => 'shop'])->assertOk();

    expect($vitrina->fresh())->status->toBe('pending')->reject_reason->toBeNull();
});

test('a user without a vitrina gets an explicit null, not an empty object', function () {
    $this->actingAs($this->owner)->getJson('/api/my-vitrina')->assertExactJson(['vitrina' => null]);
});

/* ---------- Qidiruv, tartib, statistika, e'lon, xabar ---------- */

function approvedVitrina($test, array $data = []): Vitrina
{
    $vitrina = createVitrina($test, $data);
    $vitrina->forceFill(['status' => 'approved'])->save();

    return $vitrina;
}

test('search finds available items of approved vitrinas in the chosen city', function () {
    $vitrina = approvedVitrina($this);
    $this->actingAs($this->owner);
    $this->postJson('/api/my-vitrina/items', ['title' => 'Burchak divan', 'price_mode' => 'none'])->assertCreated();
    $this->postJson('/api/my-vitrina/items', ['title' => 'Divan yotoqxona', 'price_mode' => 'none', 'is_available' => false]);
    $this->postJson('/api/my-vitrina/items', ['title' => 'Stol', 'price_mode' => 'none']);

    $this->getJson('/api/vitrina-items/search?search=divan')
        ->assertOk()->assertJsonCount(1)
        ->assertJsonPath('0.title', 'Burchak divan')
        ->assertJsonPath('0.vitrina.slug', $vitrina->slug);

    // Boshqa shaharda qidirilsa — chiqmaydi; vitrina nomi bo'yicha ham topiladi
    $this->getJson("/api/vitrina-items/search?search=divan&city_id={$this->otherCity->id}")->assertJsonCount(0);
    $this->getJson('/api/vitrina-items/search?search=mebel stol')->assertJsonCount(1)->assertJsonPath('0.title', 'Stol');

    $vitrina->forceFill(['status' => 'pending'])->save();
    $this->getJson('/api/vitrina-items/search?search=divan')->assertJsonCount(0);
});

test('the owner reorders items; foreign ids are ignored', function () {
    approvedVitrina($this);
    $this->actingAs($this->owner);
    $a = $this->postJson('/api/my-vitrina/items', ['title' => 'A', 'price_mode' => 'none'])->json('id');
    $b = $this->postJson('/api/my-vitrina/items', ['title' => 'B', 'price_mode' => 'none'])->json('id');

    $this->postJson('/api/my-vitrina/items/order', ['ids' => [$b, $a, 99999]])->assertOk();

    $this->getJson('/api/my-vitrina')->assertJsonPath('vitrina.items.0.title', 'B')->assertJsonPath('vitrina.items.1.title', 'A');
});

test('stats count contact clicks of the last 30 days and ads made from the vitrina', function () {
    $vitrina = approvedVitrina($this);
    $vitrina->forceFill(['views_count' => 42])->save();
    \Illuminate\Support\Facades\DB::table('activity_stats')->insert([
        ['date' => now()->toDateString(), 'event' => 'contact_phone', 'ref' => "v{$vitrina->id}", 'hits' => 3, 'users' => 2],
        ['date' => now()->subDays(40)->toDateString(), 'event' => 'contact_phone', 'ref' => "v{$vitrina->id}", 'hits' => 9, 'users' => 9],
        ['date' => now()->toDateString(), 'event' => 'contact_telegram', 'ref' => "v{$vitrina->id}", 'hits' => 5, 'users' => 4],
    ]);

    $this->actingAs($this->owner)->getJson('/api/my-vitrina/stats')
        ->assertOk()
        ->assertJson(['views' => 42, 'phone' => 3, 'telegram' => 5, 'ads' => 0]);
});

test('an ad links to the vitrina only when it belongs to the author', function () {
    $vitrina = approvedVitrina($this);
    $category = \App\Models\Category::create(['name' => 'Mebel', 'is_page' => true]);
    $payload = ['title' => 'Burchak divan', 'category_id' => $category->id, 'phone' => '931234567', 'price' => 100];

    $this->actingAs($this->owner)->postJson('/api/products', [...$payload, 'vitrina_id' => $vitrina->id])->assertSuccessful();
    $stranger = User::create(['name' => 'Begona', 'role' => 'user']);
    $this->actingAs($stranger)->postJson('/api/products', [...$payload, 'vitrina_id' => $vitrina->id])->assertSuccessful();

    expect(\App\Models\Product::where('vitrina_id', $vitrina->id)->count())->toBe(1);

    // E'lon sahifasida: kartadagi belgi va "Sotuvchining vitrinasi"
    $product = \App\Models\Product::where('user_id', $this->owner->id)->first();
    $this->getJson("/api/products/{$product->id}")
        ->assertJsonPath('vitrina.slug', $vitrina->slug)
        ->assertJsonPath('seller_vitrina.slug', $vitrina->slug);
});

test('the owner gets a bot message when the vitrina is approved or rejected', function () {
    config(['telegraph.bots' => []]);
    \Illuminate\Support\Facades\Http::fake(['api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
    $this->owner->forceFill(['telegram_user_id' => 777, 'bot_can_message' => true])->save();
    $vitrina = createVitrina($this);

    $this->actingAs($this->admin)->patchJson("/api/admin/vitrinas/{$vitrina->id}", ['status' => 'rejected', 'reject_reason' => 'Logo kerak'])->assertOk();
    $this->patchJson("/api/admin/vitrinas/{$vitrina->id}", ['is_verified' => true])->assertOk();
    $this->patchJson("/api/admin/vitrinas/{$vitrina->id}", ['status' => 'approved'])->assertOk();

    // Faqat holat o'zgarganda: rad etildi + tasdiqlandi (belgi qo'yish xabar yubormaydi)
    \Illuminate\Support\Facades\Http::assertSentCount(2);
    \Illuminate\Support\Facades\Http::assertSent(fn ($request) => (string) $request->data()['chat_id'] === '777'
        && str_contains($request->data()['text'] ?? '', 'Logo kerak'));
});

test('the home strip shows at most 12, verified first, in a stable daily order', function () {
    foreach (range(1, 15) as $i) {
        $user = User::create(['name' => "U{$i}", 'role' => 'user']);
        Vitrina::forceCreate([
            'user_id' => $user->id, 'name' => "V{$i}", 'slug' => "v-{$i}", 'type' => 'shop',
            'status' => 'approved', 'is_verified' => $i === 15,
        ]);
    }

    $first = $this->getJson('/api/vitrinas?strip=1')->assertOk()->assertJsonCount(12)->json();
    expect($first[0]['slug'])->toBe('v-15');

    // Shu kuni qayta so'ralsa — xuddi shu tartib
    expect(collect($this->getJson('/api/vitrinas?strip=1')->json())->pluck('id')->all())
        ->toBe(collect($first)->pluck('id')->all());

    // Katalog: hammasi, tasdiqlangan oldinda, keyin yangilari
    $this->getJson('/api/vitrinas')->assertJsonCount(15)
        ->assertJsonPath('0.slug', 'v-15')
        ->assertJsonPath('1.slug', 'v-14');
});
