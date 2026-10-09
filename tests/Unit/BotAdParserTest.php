<?php

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use App\Services\Bot\AdAiParserService;
use App\Services\Bot\TelegramChannelScraperService;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('fallback parser extracts phone, price, title, and currency from raw text', function () {
    $category = Category::firstOrCreate(['name' => 'Avto', 'is_page' => true]);
    $district = District::firstOrCreate(['name' => 'Navoiy']);
    PriceType::firstOrCreate(['name' => 'USD', 'type' => '$', 'position' => 'left']);
    PriceType::firstOrCreate(['name' => 'UZS', 'type' => 'So\'m', 'position' => 'right']);

    $parser = new AdAiParserService;
    $rawText = "Cobalt 2022 oq rang sotiladi!\nHolati ideal, gaz bor.\nNarxi: 11500 $\nTel: +998 90 123-45-67";

    $result = $parser->fallbackParse($rawText);

    expect($result)->not->toBeNull();
    expect($result['is_valid'])->toBeTrue();
    expect($result['price'])->toBe(11500);
    expect($result['currency'])->toBe('USD');
    expect($result['phone'])->toBe('998901234567');
    expect($result['title'])->toContain('Cobalt 2022');
});

test('parser ignores text without any phone number or telegram contact', function () {
    $parser = new AdAiParserService;
    $setting = BotSetting::current();
    $rawText = "Bugun havo juda yaxshi bo'lyapti, do'stlar!";

    $result = $parser->parse($rawText, $setting);

    expect($result)->toBeNull();
});

test('parser ignores spam messages', function () {
    $parser = new AdAiParserService;
    $setting = BotSetting::current();
    $rawText = "1xBet rasmiy kanali orqali har kuni pul ishlang! Aloqa: +998 90 123-45-67";

    $result = $parser->parse($rawText, $setting);

    expect($result)->toBeNull();
});

test('scraper extracts message id, text and image from html block', function () {
    $scraper = new TelegramChannelScraperService;
    $html = <<<HTML
    <div class="tgme_widget_message" data-post="navoiy_bozor/555">
        <a class="tgme_widget_message_photo_wrap" style="background-image:url('https://cdn4.telegram-cdn.org/file/sample.jpg')"></a>
        <div class="tgme_widget_message_text">Spark 2019 sotiladi<br>Narxi: 7000$ Tel: +998 91 333-22-11</div>
    </div>
HTML;

    $posts = $scraper->parseHtml($html, 'navoiy_bozor', 10);

    expect($posts)->toHaveCount(1);
    expect($posts[0]['message_id'])->toBe(555);
    expect($posts[0]['text'])->toContain('Spark 2019 sotiladi');
    expect($posts[0]['text'])->toContain('7000$');
    expect($posts[0]['images'])->toContain('https://cdn4.telegram-cdn.org/file/sample.jpg');
});
