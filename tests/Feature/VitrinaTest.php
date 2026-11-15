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
