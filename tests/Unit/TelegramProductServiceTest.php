<?php

use App\Models\Category;
use App\Models\District;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParameterValue;
use App\Models\ProductTelegramId;
use App\Services\TelegramProductService;
use DefStudio\Telegraph\Facades\Telegraph as TelegraphFacade;
use DefStudio\Telegraph\Telegraph as TelegraphClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('telegram caption includes product parameters and units', function () {
    $parameter = new Parameter([
        'unit' => 'ta',
    ]);
    $parameter->setAttribute('title', 'Xonalar soni');

    $parameterValue = new ProductParameterValue([
        'value' => '3',
    ]);
    $parameterValue->setRelation('parameter', $parameter);

    $product = new Product([
        'title' => 'Uy sotiladi',
        'description' => 'Shinam va keng uy',
        'price' => 250000000,
        'phone' => '901234567',
    ]);
    $category = new Category;
    $category->setAttribute('name', 'Uy-joy');
    $district = new District;
    $district->setAttribute('name', 'Navoiy');
    $product->setRelation('price_type', null);
    $product->setRelation('district', $district);
    $product->setRelation('category', $category);
    $product->setRelation('parameter_values', new Collection([$parameterValue]));

    $method = new ReflectionMethod(TelegramProductService::class, 'makeCaption');
    $caption = $method->invoke(new TelegramProductService, $product);
    $captionWithoutPhoneIcon = str_replace('📱', '', $caption);

    expect($caption)
        ->toStartWith("#Navoiy #Uy_joy\n\n<b>UY SOTILADI</b>\n<b>250 000 000</b>")
        ->toContain('<i>Shinam va keng uy</i>')
        ->toContain('(Xonalar soni - <b>3 ta</b>)')
        ->toContain('Xonalar soni')
        ->toContain('3 ta')
        ->toContain('📱 <code>901234567</code>')
        ->toContain('901234567')
        ->not->toContain('Navoiy viloyati, Uy-joy')
        ->not->toContain('YANGI E\'LON')
        ->not->toContain('Asosiy xususiyatlar')
        ->not->toContain('━━━━━━━━━━━━━━');

    expect(preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $captionWithoutPhoneIcon))->toBe(0);
});

test('telegram text post is edited in place after product changes', function () {
    TelegraphFacade::fake();
    config()->set('telegraph.bot_token', 'test-token');

    $product = makeTelegramTestProduct();
    $telegramId = new ProductTelegramId;
    $telegramId->setAttribute('message_id', 456);
    $method = new ReflectionMethod(TelegramProductService::class, 'editChannelMessage');

    $method->invoke(new TelegramProductService, $product, $telegramId, '-1001234567890', false);

    TelegraphFacade::assertSentData(TelegraphClient::ENDPOINT_EDIT_MESSAGE, [
        'message_id' => 456,
    ], false);
    TelegraphFacade::assertSentData(TelegraphClient::ENDPOINT_EDIT_MESSAGE, [
        'text' => '#Navoiy #Uy_joy',
    ], false);
});

test('telegram photo post is edited in place with the refreshed caption', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);
    config()->set('telegraph.bot_token', 'test-token');

    $product = makeTelegramTestProduct();
    $product->setRelation('images', new Collection([
        new ProductImage(['src' => 'products/telegram-test.webp']),
    ]));
    $telegramId = new ProductTelegramId;
    $telegramId->setAttribute('message_id', 789);
    $method = new ReflectionMethod(TelegramProductService::class, 'editChannelMessage');

    $method->invoke(new TelegramProductService, $product, $telegramId, '-1001234567890', true);

    Http::assertSent(function (HttpRequest $request): bool {
        $media = json_decode($request->data()['media'], true);

        return str_ends_with(
            (string) parse_url($request->url(), PHP_URL_PATH),
            '/'.TelegraphClient::ENDPOINT_EDIT_MEDIA,
        )
            && $request->data()['message_id'] === 789
            && str_contains($media['caption'], '#Navoiy #Uy_joy')
            && str_contains($media['caption'], '<b>YANGI SARLAVHA</b>');
    });
});

function makeTelegramTestProduct(): Product
{
    $product = new Product([
        'title' => 'Yangi sarlavha',
        'description' => 'Yangilangan tavsif',
        'price' => 120000,
        'phone' => '901234567',
    ]);
    $category = new Category;
    $category->setAttribute('name', 'Uy-joy');
    $district = new District;
    $district->setAttribute('name', 'Navoiy');
    $product->setRelation('price_type', null);
    $product->setRelation('district', $district);
    $product->setRelation('category', $category);
    $product->setRelation('parameter_values', new Collection);
    $product->setRelation('images', new Collection);

    return $product;
}
