<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ChannelLivePostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.telegram.channels' => ['-100500'],
        'services.telegram.mini_app_url' => 'https://t.me/TestBot',
        'telegraph.bot_token' => 'test-token',
    ]);
});

function liveAd(Category $category, $publishedAt = null, $expiresAt = null): Product
{
    $user = User::firstOrCreate(['name' => 'Sotuvchi'], ['role' => 'user']);

    // published_at mass assignment'da yo'q — ProductService ham uni alohida yozadi
    $product = new Product([
        'title' => "E'lon", 'category_id' => $category->id, 'user_id' => $user->id,
        'expires_at' => $expiresAt ?? now()->addDay(),
    ]);
    $product->forceFill(['published_at' => $publishedAt ?? now()])->save();

    return $product;
}

/** Telegram javoblari: hammasi ok (message_id 77); $GLOBALS['telegramOverrides'] — metod bo'yicha boshqa javob */
function fakeTelegram(): void
{
    $GLOBALS['telegramOverrides'] = [];
    Http::fake(function (Request $request) {
        foreach ($GLOBALS['telegramOverrides'] as $method => $response) {
            if (str_ends_with($request->url(), "/{$method}")) {
                return Http::response($response);
            }
        }

        return Http::response(['ok' => true, 'result' => ['message_id' => 77]]);
    });
}

function telegramCalls(string $method): int
{
    return Http::recorded(fn (Request $request) => str_ends_with($request->url(), "/{$method}"))->count();
}

test('live post lists featured sections with active and today counts, including subsections', function () {
    $rides = Category::create(['name' => "Yo'lovchilar", 'image' => 'CarTaxiFront', 'is_page' => true, 'is_featured' => true, 'featured_order' => 1]);
    $services = Category::create(['name' => 'Xizmatlar', 'image' => 'Wrench', 'is_featured' => true, 'featured_order' => 2]);
    $home = Category::create(['name' => 'Uy xizmatlari', 'parent_id' => $services->id, 'is_page' => true]);

    liveAd($rides);
    liveAd($rides, now()->subDays(2));
    liveAd($rides, expiresAt: now()->subHour()); // muddati o'tgan — sanalmaydi
    liveAd($home, now()->subDays(3));

    $content = app(ChannelLivePostService::class)->content();

    expect($content['body'])
        ->toContain("🚕 <b>Yo'lovchilar</b> — 2 ta · bugun +1")
        ->toContain('🔧 <b>Xizmatlar</b> — 1 ta')
        ->not->toContain('Xizmatlar</b> — 1 ta · bugun')
        ->and($content['buttons'][0])->toBe(["🚕 Yo'lovchilar", "https://t.me/TestBot?startapp=cat_{$rides->id}"])
        ->and($content['buttons'][1][1])->toBe("https://t.me/TestBot?startapp=cats_{$services->id}");
});

test('first sync posts and silently pins; unchanged numbers send nothing; changes edit the same post', function () {
    $rides = Category::create(['name' => "Yo'lovchilar", 'is_page' => true, 'is_featured' => true]);
    fakeTelegram();
    $service = app(ChannelLivePostService::class);

    expect($service->sync())->toBe(['-100500' => 'sent'])
        ->and(telegramCalls('sendMessage'))->toBe(1)
        ->and(telegramCalls('pinChatMessage'))->toBe(1)
        ->and(ChannelLivePostService::isPosted())->toBeTrue();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/pinChatMessage')
        && $request['disable_notification'] === true);

    expect($service->sync())->toBe(['-100500' => 'unchanged'])
        ->and(telegramCalls('editMessageText'))->toBe(0);

    liveAd($rides);
    expect($service->sync())->toBe(['-100500' => 'edited'])
        ->and(telegramCalls('editMessageText'))->toBe(1)
        ->and(telegramCalls('sendMessage'))->toBe(1);
});

test('a post deleted by hand is written again', function () {
    Category::create(['name' => "Yo'lovchilar", 'is_page' => true, 'is_featured' => true]);
    fakeTelegram();
    $service = app(ChannelLivePostService::class);
    $service->sync();

    $GLOBALS['telegramOverrides'] = ['editMessageText' => ['ok' => false, 'description' => 'Bad Request: message to edit not found']];

    // Birinchi post + qayta yozilgani
    expect($service->sync(force: true))->toBe(['-100500' => 'sent'])
        ->and(telegramCalls('sendMessage'))->toBe(2);
});

test('admin can turn the live post on and off', function () {
    Laravel\Sanctum\Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));
    Category::create(['name' => "Yo'lovchilar", 'is_page' => true, 'is_featured' => true]);
    fakeTelegram();

    $this->postJson('/api/admin/channel-live-post', ['enabled' => true])->assertOk();
    expect(ChannelLivePostService::enabled())->toBeTrue();

    $this->postJson('/api/admin/channel-live-post', ['enabled' => false])->assertOk();
    expect(ChannelLivePostService::enabled())->toBeFalse()
        ->and(ChannelLivePostService::isPosted())->toBeFalse()
        ->and(telegramCalls('deleteMessage'))->toBe(1);
});

test('sections without ads are left out of the text but keep their button', function () {
    Category::create(['name' => 'Kafe-Restoranlar', 'is_page' => true, 'is_featured' => true]);

    $content = app(ChannelLivePostService::class)->content();

    expect($content['body'])->not->toContain('Kafe-Restoranlar')
        ->and($content['buttons'][0][0])->toContain('Kafe-Restoranlar');
});
