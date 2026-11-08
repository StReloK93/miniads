<?php

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\User;
use App\Services\Bot\BotAdImportService;
use App\Services\Bot\TelegramChannelScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

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

/** Guruhdan nusxalangan e'lon: kanal yo'q, kontakt faqat @nick */
const PASTED_TEXT = "Sotiladi: Lacetti 2012, oq rang, holati zo'r. Narxi 9500\$. Navoiy shahri. Murojaat: @muhtasham_hr";

function postPastedAd(array $images = [], array $extra = []): \Illuminate\Testing\TestResponse
{
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    return test()->post('/api/admin/bot-import-text', ['text' => PASTED_TEXT, 'images' => $images, 'publish' => '0', ...$extra], ['Accept' => 'application/json']);
}

test('pasted text becomes an ad with uploaded photos and the nick as contact', function () {
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Lacetti 2012', 'price' => 9500, 'category_id' => 3])));

    $response = postPastedAd([
        UploadedFile::fake()->createWithContent('a.jpg', jpegBytes()),
        UploadedFile::fake()->createWithContent('b.jpg', jpegBytes()),
    ]);
    $product = Product::with('images')->first();

    try {
        $response->assertOk()->assertJsonPath('status', 'imported')->assertJsonCount(2, 'product.images');
        expect($product->title)->toBe('Lacetti 2012')
            ->and($product->phone)->toBe('@muhtasham_hr')
            ->and($product->is_bot)->toBeTrue()
            ->and($product->source_channel)->toBeNull();
    } finally {
        foreach ($product?->images ?? [] as $image) {
            File::delete(public_path('storage/' . $image->src));
            File::delete(public_path('storage/' . $image->thumb_src));
        }
    }
});

test('photos the admin pasted are kept even in a category without images', function () {
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Svarchik kerak', 'category_id' => 2])));

    postPastedAd([UploadedFile::fake()->createWithContent('a.jpg', jpegBytes())])
        ->assertOk()
        ->assertJsonPath('product.category', 'Doimiy ish')
        ->assertJsonCount(1, 'product.images');
});

test('pasted ad can be permanent or last a chosen number of days', function () {
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Istiqlol kafe', 'category_id' => 3])));

    $id = postPastedAd([], ['duration' => 'permanent'])->assertOk()->json('product.id');
    $product = Product::find($id);
    expect($product->expires_at)->toBeNull()
        ->and(Product::active()->whereKey($id)->exists())->toBeTrue();

    $this->patchJson("/api/admin/products/{$id}/expiry", ['duration' => 'days', 'days' => 5])->assertOk();
    expect(\Illuminate\Support\Carbon::parse($product->fresh()->expires_at)->isSameDay(now()->addDays(5)))->toBeTrue();
});

test('the same pasted text is not added twice', function () {
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Lacetti', 'category_id' => 3])));

    postPastedAd()->assertOk();
    postPastedAd()->assertOk()->assertJsonPath('status', 'exists');

    expect(Product::count())->toBe(1);
});

test('only admins can import pasted text', function () {
    Sanctum::actingAs(User::create(['name' => 'User', 'role' => 'user']));

    $this->postJson('/api/admin/bot-import-text', ['text' => PASTED_TEXT])->assertForbidden();
});

test('a price the AI made up is dropped, the ad is negotiable', function () {
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Divan', 'price' => 1500, 'currency' => 'USD', 'category_id' => 3])));
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    $this->postJson('/api/admin/bot-import-text', ['text' => "Divan sotiladi, holati yaxshi. Navoiy. Tel: 90 123 45 67"])
        ->assertOk()->assertJsonPath('product.price', null);
});

test('a price written in the text is kept, also as "mln"', function () {
    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Divan', 'price' => 1500000, 'category_id' => 3])));
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    $this->postJson('/api/admin/bot-import-text', ['text' => "Divan sotiladi. Narxi 1,5 mln so'm. Tel: 90 123 45 67"])
        ->assertOk()->assertJsonPath('product.price', 1500000);
});

test('pasted text without a phone or @nick is not imported', function () {
    Http::fake();
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    $this->postJson('/api/admin/bot-import-text', ['text' => "Divan sotiladi, holati yaxshi, Navoiy shahri. Narxi 200\$"])
        ->assertStatus(422)->assertJsonPath('status', 'invalid')
        ->assertJsonPath('message', fn ($m) => str_contains($m, "Murojaat uchun ma'lumot yetarli emas"));

    Http::assertNothingSent();
    expect(Product::count())->toBe(0);
});

test('a cafe ad gets no price even when the AI sets one, and the phone is formatted', function () {
    $cafe = Category::create(['name' => 'Kafe va restoranlar', 'is_page' => true, 'with_price' => false, 'listing_duration_days' => 30]);
    Http::fake(fn () => Http::response(chosenGeminiReply([
        'is_valid' => true, 'title' => 'Istiqlol kafe', 'price' => 50000, 'currency' => 'UZS', 'phone' => '+998901234567',
        'description' => "Milliy taomlar.\n\n• Narxi: 50 000 so'm\n• Bog'lanish uchun: +998 90 123 45 67", 'category_id' => $cafe->id,
    ])));
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    $id = $this->postJson('/api/admin/bot-import-text', ['text' => "Istiqlol kafe, milliy taomlar 50 000 so'm dan. Tel: +998 90 123 45 67"])
        ->assertOk()->assertJsonPath('product.price', null)->json('product.id');

    $product = Product::find($id);
    expect($product->phone)->toBe('90-123-45-67')
        ->and($product->description)->not->toContain('Narxi')
        ->and($product->description)->toContain("Bog'lanish uchun: 90-123-45-67");
});

test('AI fills only the category parameters that are really in the text', function () {
    $cars = Category::create(['name' => 'Yengil avto', 'is_page' => true, 'listing_duration_days' => 30]);
    $gearbox = \App\Models\Parameter::create(['title' => 'Uzatmalar qutisi', 'placeholder' => '-', 'type' => 'string', 'component' => 'FieldSelect',
        'options' => ['Avtomat', 'Mexanika'], 'translations' => ['ru' => ['options' => ['Автомат', 'Механика']]]]);
    $year = \App\Models\Parameter::create(['title' => 'Yili', 'placeholder' => '-', 'type' => 'number', 'component' => 'FieldNumber', 'unit' => 'yil']);
    $mileage = \App\Models\Parameter::create(['title' => 'Probeg', 'placeholder' => '-', 'type' => 'number', 'component' => 'FieldNumber', 'unit' => 'km']);
    $color = \App\Models\Parameter::create(['title' => 'Rangi', 'placeholder' => '-', 'type' => 'string', 'component' => 'FieldSelect', 'options' => ['Oq', 'Qora']]);
    $rooms = \App\Models\Parameter::create(['title' => 'Xonalar', 'placeholder' => '-', 'type' => 'number', 'component' => 'FieldNumber']);
    foreach ([$gearbox, $year, $mileage, $color] as $i => $parameter) {
        $cars->parameters()->attach($parameter->id, ['sort_order' => $i, 'is_required' => false]);
    }

    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Cobalt 2019', 'price' => 11500, 'currency' => 'USD',
        'category_id' => $cars->id, 'parameters' => [
            (string) $gearbox->id => 'Автомат',   // ruscha — asl "Avtomat"ga o'giriladi
            (string) $year->id => 2019,
            (string) $mileage->id => 40000,       // matnda "40 ming"
            (string) $color->id => 'Kumush',      // ro'yxatda yo'q — tashlanadi
            (string) $rooms->id => 3,             // boshqa kategoriya parametri — tashlanadi
        ]])));
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    $id = $this->postJson('/api/admin/bot-import-text', ['text' => "Cobalt 2019 avtomat, probeg 40 ming. Narxi 11 500\$. Tel: 90 123 45 67"])
        ->assertOk()->json('product.id');

    $values = \App\Models\ProductParameterValue::where('product_id', $id)->pluck('value', 'parameter_id')->all();
    expect($values)->toBe([$gearbox->id => 'Avtomat', $year->id => '2019', $mileage->id => '40000']);
    Http::assertSent(fn ($request) => str_contains($request->body(), "#{$gearbox->id} Uzatmalar qutisi: Avtomat|Mexanika")
        && str_contains($request->body(), "#{$year->id} Yili: son (yil)"));
});

test('a made-up number parameter is dropped', function () {
    $flats = Category::create(['name' => 'Kvartira', 'is_page' => true, 'listing_duration_days' => 30]);
    $area = \App\Models\Parameter::create(['title' => 'Maydoni', 'placeholder' => '-', 'type' => 'number', 'component' => 'FieldNumber', 'unit' => 'm²']);
    $flats->parameters()->attach($area->id, ['sort_order' => 0, 'is_required' => false]);

    Http::fake(fn () => Http::response(chosenGeminiReply(['is_valid' => true, 'title' => 'Kvartira', 'category_id' => $flats->id,
        'parameters' => [(string) $area->id => 65]])));
    Sanctum::actingAs(User::create(['name' => 'Administrator', 'role' => 'admin']));

    $id = $this->postJson('/api/admin/bot-import-text', ['text' => "3 xonali kvartira sotiladi, ta'mirli. Tel: 90 123 45 67"])
        ->assertOk()->json('product.id');

    expect(\App\Models\ProductParameterValue::where('product_id', $id)->count())->toBe(0);
});
