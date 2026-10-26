<?php

use App\Models\BotSeenPost;
use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use App\Models\Product;
use App\Services\Bot\AdPrefilter;
use App\Services\Bot\BotAdImportService;
use App\Services\Bot\TelegramChannelScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const REAL_AD = "Cobalt 2022 oq rang sotiladi\nHolati ideal, probeg 40 ming\nNarxi: 11500 \$\nTel: +998 90 123-45-67";
const NEWS_POST = "Uchquduq tumani hokimi bugun yig'ilish o'tkazdi va yangi maktab qurilishini ma'lum qildi.\n\n@Uchquduq_24";
const AMBIGUOUS_POST = "Bugun bozorda odam ko'p edi, hamma xursand. Batafsil: +998 91 222-33-44";

function fakeScraper(array $posts): void
{
    app()->instance(TelegramChannelScraperService::class, new class($posts) extends TelegramChannelScraperService {
        public function __construct(private array $posts) {}

        public function fetchChannelPosts(string $channel, int $limit = 50): array
        {
            return $this->posts;
        }
    });
}

function post(int $id, string $text): array
{
    return ['channel' => 'Uchquduq_24', 'message_id' => $id, 'text' => $text, 'images' => [], 'date' => now()->toIso8601String()];
}

function geminiReply(array $json): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]],
        'usageMetadata' => ['totalTokenCount' => 1500],
    ];
}

beforeEach(function () {
    Category::create(['name' => 'Yengil avto', 'is_page' => true, 'listing_duration_days' => 30]);
    Category::create(['name' => 'Boshqa', 'is_page' => true, 'listing_duration_days' => 30]);
    District::create(['name' => 'Uchquduq']);
    PriceType::create(['name' => 'UZS', 'type' => "so'm", 'position' => 'right']);
    PriceType::create(['name' => 'USD', 'type' => '$', 'position' => 'left']);

    BotSetting::current()->update([
        'is_enabled' => true,
        'channels' => '@Uchquduq_24',
        'ai_provider' => 'gemini',
        'ai_api_key' => 'test-key',
        'telegram_post_enabled' => false,
        'hourly_limit' => 10,
    ]);
});

test('a post is sent to AI only once, even when AI rejects it', function () {
    fakeScraper([post(1, REAL_AD), post(2, AMBIGUOUS_POST)]);

    Http::fake(function (Request $request) {
        $isAd = str_contains($request->data()['contents'][0]['parts'][0]['text'], 'Cobalt');

        return Http::response(geminiReply($isAd
            ? ['is_valid' => true, 'title' => 'Cobalt 2022 oq rang', 'description' => 'Cobalt', 'price' => 11500, 'currency' => 'USD', 'phone' => '998901234567', 'category_id' => 1, 'district_id' => 1]
            : ['is_valid' => false, 'reason' => 'suhbat']));
    });

    $service = app(BotAdImportService::class);
    $first = $service->importBatch();
    $second = $service->importBatch();

    Http::assertSentCount(2); // 2 post x 1 marta. Ilgari rad etilgan post har soatda qayta yuborilardi
    expect($first['imported_count'])->toBe(1)
        ->and($second['imported_count'])->toBe(0)
        ->and(BotSeenPost::where('message_id', 2)->value('status'))->toBe('rejected')
        ->and(Product::count())->toBe(1);
});

test('AI rejection is respected instead of saving the post through the fallback parser', function () {
    fakeScraper([post(3, AMBIGUOUS_POST)]);
    Http::fake(fn() => Http::response(geminiReply(['is_valid' => false, 'reason' => 'suhbat'])));

    app(BotAdImportService::class)->importBatch();

    expect(Product::count())->toBe(0);
});

test('news posts are filtered without calling AI', function () {
    fakeScraper([post(4, NEWS_POST)]);
    Http::fake();

    app(BotAdImportService::class)->importBatch();

    Http::assertNothingSent();
    expect(BotSeenPost::where('message_id', 4)->value('status'))->toBe('filtered');
});

test('AI client error stops the run without retrying and leaves posts for the next run', function () {
    fakeScraper([post(5, REAL_AD), post(6, str_replace('Cobalt', 'Nexia', REAL_AD))]);
    Http::fake(fn() => Http::response(['error' => ['message' => 'Request contains an invalid argument.']], 400));

    $result = app(BotAdImportService::class)->importBatch();

    $geminiCalls = collect(Http::recorded())->filter(fn($pair) => str_contains($pair[0]->url(), 'generativelanguage'));
    $alerts = collect(Http::recorded())->filter(fn($pair) => str_contains($pair[0]->url(), 'api.telegram.org'));

    // 400 da qayta urinish yo'q, ikkinchi post AI'ga yuborilmaydi; admin'ga bitta ogohlantirish ketadi
    expect($geminiCalls)->toHaveCount(1)
        ->and($alerts->count())->toBeLessThanOrEqual(1)
        ->and($result['status'])->toBe('ai_stopped')
        ->and(BotSeenPost::count())->toBe(0);
});

test('daily token limit stops AI calls', function () {
    config(['services.ai_daily_token_limit' => 1000]);
    fakeScraper([post(7, REAL_AD), post(8, str_replace('Cobalt', 'Nexia', REAL_AD))]);
    Http::fake(fn() => Http::response(geminiReply(['is_valid' => true, 'title' => 'Avto', 'description' => 'Avto', 'category_id' => 1, 'district_id' => 1])));

    $result = app(BotAdImportService::class)->importBatch();

    Http::assertSentCount(1); // birinchi javob 1500 token => limit tugadi
    expect($result['status'])->toBe('ai_stopped')
        ->and($result['imported_count'])->toBe(1);
});

test('prefilter keeps real ads and removes channel signature as contact', function () {
    $prefilter = new AdPrefilter;

    expect($prefilter->rejectReason(REAL_AD))->toBeNull()
        ->and($prefilter->rejectReason("iPhone 13 128gb ideal\n93 123 45 67"))->toBeNull()
        ->and($prefilter->rejectReason("2 xonali kvartira ijaraga beriladi\n@ali_uy"))->toBeNull()
        ->and($prefilter->rejectReason(NEWS_POST))->not->toBeNull()
        ->and($prefilter->rejectReason("Hurmatli yurtdoshlar, bayramingiz muborak! @Uchquduq_24"))->not->toBeNull()
        ->and($prefilter->stripChannelNoise(NEWS_POST, 'Uchquduq_24'))->not->toContain('@Uchquduq_24')
        ->and($prefilter->stripChannelNoise("Spark sotiladi @ali_avto\nhttps://t.me/Uchquduq_24", 'Uchquduq_24'))->toContain('@ali_avto');
});
