<?php

use App\Http\Controllers\ProductDraftController;
use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function draftGeminiReply(array $json): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]],
        'usageMetadata' => ['totalTokenCount' => 900],
    ];
}

beforeEach(function () {
    $this->shop = Category::create(['name' => "Do'kon", 'is_page' => true, 'with_price' => true, 'listing_duration_days' => 30]);
    Category::create(['name' => 'Boshqa', 'is_page' => true, 'listing_duration_days' => 30]);
    // AdAiParserService::guessDistrictId hududlarni haqiqiy bazadagi id'lar bo'yicha taniydi (Navoiy = 3)
    foreach (['Uchquduq', 'Zarafshon', 'Navoiy', 'Karmana', 'Qiziltepa'] as $name) {
        District::create(['name' => $name]);
    }
    PriceType::create(['name' => 'UZS', 'type' => "so'm", 'position' => 'right']);
    BotSetting::current()->update(['ai_provider' => 'gemini', 'ai_api_key' => 'test-key']);

    $this->user = User::create(['name' => 'Ali']);
    Sanctum::actingAs($this->user);
});

test('short text becomes a form draft and missing fields are listed', function () {
    Http::fake(fn () => Http::response(draftGeminiReply([
        'is_valid' => true, 'title' => "Do'kon sotiladi", 'description' => "Do'kon sotiladi.\n\n• Bog'lanish uchun: 93-123-45-67",
        'category_id' => $this->shop->id, 'district_id' => 3, 'price' => 50000000,
    ])));

    $response = $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi kelishiladi"])->assertOk();

    expect($response->json('draft.title'))->toBe("Do'kon sotiladi")
        ->and($response->json('draft.category_id'))->toBe($this->shop->id)
        // Matnda narx ham, shahar ham yo'q: AI'ning taxmini qabul qilinmaydi
        ->and($response->json('draft.price'))->toBeNull()
        ->and($response->json('draft.description'))->not->toContain("Bog'lanish")
        ->and($response->json('missing'))->toBe(['phone', 'price'])
        ->and($response->json('duplicate'))->toBeNull()
        ->and($response->json('remaining'))->toBe(4);

    Http::assertSent(fn (Request $request) => str_contains($request->data()['contents'][0]['parts'][0]['text'], "foydalanuvchi o'z e'loni"));
});

test('city and phone written in the text are not reported as missing', function () {
    Http::fake(fn () => Http::response(draftGeminiReply([
        'is_valid' => true, 'title' => "Do'kon sotiladi", 'category_id' => $this->shop->id, 'phone' => '93-123-45-67', 'price' => 50000000,
    ])));

    $response = $this->postJson('/api/products/ai-draft', ['text' => "Navoiyda do'kon sotiladi 50 mln so'm, tel 93 123 45 67"])->assertOk();

    expect($response->json('draft.district_id'))->toBe(3)
        ->and($response->json('draft.phone'))->toBe('93-123-45-67')
        ->and($response->json('draft.price'))->toBe(50000000)
        ->and($response->json('missing'))->toBe([]);
});

test('a user gets at most five drafts a day, admins are not limited', function () {
    Http::fake(fn () => Http::response(draftGeminiReply(['is_valid' => true, 'title' => "Do'kon", 'category_id' => $this->shop->id])));

    foreach (range(1, ProductDraftController::DAILY_LIMIT) as $attempt) {
        $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi"])->assertOk();
    }
    $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi"])
        ->assertStatus(429)
        ->assertJsonPath('remaining', 0);
    Http::assertSentCount(ProductDraftController::DAILY_LIMIT);

    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));
    foreach (range(1, ProductDraftController::DAILY_LIMIT + 1) as $attempt) {
        $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi"])->assertOk()->assertJsonPath('remaining', null);
    }
});

test('a similar active ad of the same user is reported as a duplicate', function () {
    Http::fake(fn () => Http::response(draftGeminiReply(['is_valid' => true, 'title' => "Do'kon sotiladi", 'category_id' => $this->shop->id])));
    $existing = Product::create(['title' => "Do'kon sotiladi!", 'category_id' => $this->shop->id, 'user_id' => $this->user->id]);
    $existing->forceFill(['expires_at' => now()->addDays(10)])->save();

    $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi kelishiladi"])
        ->assertOk()
        ->assertJsonPath('duplicate.id', $existing->id);

    // Muddati tugagan e'lon — dublikat emas
    $existing->forceFill(['expires_at' => now()->subDay()])->save();
    $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi kelishiladi"])
        ->assertOk()
        ->assertJsonPath('duplicate', null);
});

test('when AI is unavailable the draft is made by rules', function () {
    Http::fake(fn () => Http::response(['error' => ['message' => 'quota']], 400));

    $response = $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi, tel 93 123 45 67"])->assertOk();

    expect($response->json('draft.title'))->not->toBeEmpty()
        ->and($response->json('draft.phone'))->toBe('93-123-45-67');
});

test('blocked users and empty text are rejected', function () {
    $this->postJson('/api/products/ai-draft', ['text' => 'abc'])->assertStatus(422);

    $this->user->forceFill(['blocked_at' => now()])->save();
    $this->postJson('/api/products/ai-draft', ['text' => "Do'kon sotiladi"])->assertStatus(403);
});
