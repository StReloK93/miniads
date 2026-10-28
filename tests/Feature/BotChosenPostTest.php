<?php

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use App\Models\Product;
use App\Services\Bot\BotAdImportService;
use App\Services\Bot\TelegramChannelScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/** Admin havola bergan post: kontakt yo'q va yangilikka o'xshaydi — avto-importda filtrlanardi */
const CHOSEN_TEXT = "Ish bor! Zavodga svarchik va malyar kerak. Ish vaqti 9:00-18:00, tushlik bepul.";

function fakeChosenPost(?array $post): void
{
    app()->instance(TelegramChannelScraperService::class, new class($post) extends TelegramChannelScraperService {
        public function __construct(private ?array $post) {}

        public function fetchPost(string $channel, int $messageId): ?array
        {
            return $this->post;
        }
    });
}

function chosenPost(array $images = []): array
{
    return ['channel' => 'Navoiy_kerak_ishchi', 'message_id' => 4253, 'text' => CHOSEN_TEXT, 'images' => $images, 'date' => now()->subMonth()->toIso8601String()];
}

function chosenGeminiReply(array $json): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]],
        'usageMetadata' => ['totalTokenCount' => 900],
    ];
}

function jpegBytes(): string
{
    $image = imagecreatetruecolor(400, 300);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 60, 60));
    ob_start();
    imagejpeg($image, null, 90);
    imagedestroy($image);

    return ob_get_clean();
}

beforeEach(function () {
    // Ilovadagi kabi: ishda rasm o'chirilgan (with_image = false), boshqasida yoqilgan
    $jobs = Category::create(['name' => 'Ish', 'with_image' => false, 'listing_duration_days' => 30]);
    Category::create(['name' => 'Doimiy ish', 'parent_id' => $jobs->id, 'is_page' => true, 'with_image' => false, 'listing_duration_days' => 30]);
    Category::create(['name' => 'Boshqa', 'is_page' => true, 'with_image' => true, 'listing_duration_days' => 30]);
    // AdAiParserService::guessDistrictId hududlarni haqiqiy bazadagi id'lar bo'yicha taniydi (Navoiy = 3)
    foreach (['Uchquduq', 'Zarafshon', 'Navoiy', 'Karmana', 'Qiziltepa'] as $name) {
        District::create(['name' => $name]);
    }
    PriceType::create(['name' => 'UZS', 'type' => "so'm", 'position' => 'right']);

    BotSetting::current()->update(['ai_provider' => 'gemini', 'ai_api_key' => 'test-key', 'telegram_post_enabled' => false]);
});

test('chosen post is imported without filters and AI is told not to reject it', function () {
    fakeChosenPost(chosenPost());
    Http::fake(fn () => Http::response(chosenGeminiReply([
        'is_valid' => true, 'title' => 'Zavodga svarchik va malyar kerak', 'description' => 'Ish bor', 'category_id' => 2, 'district_id' => 1,
    ])));

    $result = app(BotAdImportService::class)->importChosen('https://t.me/Navoiy_kerak_ishchi/4253');

    expect($result['status'])->toBe('imported')
        ->and($result['parsed_by'])->toBe('ai')
        ->and($result['product']->title)->toBe('Zavodga svarchik va malyar kerak')
        ->and($result['product']->is_bot)->toBeTrue()
        ->and($result['product']->source_channel)->toBe('Navoiy_kerak_ishchi')
        ->and($result['product']->source_message_id)->toBe(4253);

    Http::assertSent(fn (Request $request) => str_contains($request->data()['contents'][0]['parts'][0]['text'], "admin o'zi tanlagan"));
});

test('chosen post is still imported when AI rejects it', function () {
    fakeChosenPost(chosenPost());
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => false, 'reason' => 'reklama'])));

    $result = app(BotAdImportService::class)->importChosen('https://t.me/Navoiy_kerak_ishchi/4253');

    expect($result['status'])->toBe('imported')
        ->and($result['parsed_by'])->toBe('rules')
        ->and(Product::count())->toBe(1);
});

test('the same chosen post is not imported twice', function () {
    fakeChosenPost(chosenPost());
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Ish', 'category_id' => 2])));
    $service = app(BotAdImportService::class);

    $service->importChosen('https://t.me/Navoiy_kerak_ishchi/4253');
    $second = $service->importChosen('t.me/s/Navoiy_kerak_ishchi/4253?single');

    expect($second['status'])->toBe('exists')
        ->and(Product::count())->toBe(1);
});

test('chosen post keeps photos only where the category allows them', function (int $categoryId, int $expectedImages) {
    fakeChosenPost(chosenPost(['https://cdn.telegram.test/a.jpg', 'https://cdn.telegram.test/b.jpg']));
    Http::fake(fn (Request $request) => str_contains($request->url(), 'cdn.telegram.test')
        ? Http::response(jpegBytes())
        : Http::response(chosenGeminiReply(['is_valid' => true, 'title' => "E'lon", 'category_id' => $categoryId])));

    $result = app(BotAdImportService::class)->importChosen('https://t.me/Navoiy_kerak_ishchi/4253');

    try {
        expect($result['product']->category_id)->toBe($categoryId)
            ->and($result['product']->images)->toHaveCount($expectedImages);
    } finally {
        foreach ($result['product']->images as $image) {
            File::delete(public_path('storage/' . $image->src));
            File::delete(public_path('storage/' . $image->thumb_src));
        }
    }
})->with([
    "rasmi o'chirilgan kategoriya (Doimiy ish)" => [2, 0],
    "rasmi bor kategoriya" => [3, 2],
]);

test('private links and missing posts are rejected with a reason', function () {
    fakeChosenPost(null);
    $service = app(BotAdImportService::class);

    expect($service->importChosen('https://t.me/c/123/45')['status'])->toBe('invalid')
        ->and($service->importChosen('https://t.me/Navoiy_kerak_ishchi/999')['status'])->toBe('not_found')
        ->and(Product::count())->toBe(0);
});
