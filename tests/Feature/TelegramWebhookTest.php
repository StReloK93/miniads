<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function webhookMessage(string $text, string $language = 'uz'): array
{
    return ['message' => [
        'text' => $text,
        'chat' => ['id' => 555, 'type' => 'private'],
        'from' => ['id' => 555, 'language_code' => $language],
    ]];
}

function sentMarkup(Request $request): array
{
    $markup = $request->data()['reply_markup'] ?? [];

    return is_string($markup) ? json_decode($markup, true) : $markup;
}

beforeEach(function () {
    config([
        'services.telegram.channel_url' => 'https://t.me/navoiy_elonlaruz',
        'services.telegram.web_app_url' => 'https://elonlar.test',
    ]);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
});

test('/start mentions the channel and offers "post an ad" under the message and at the bottom', function () {
    $this->postJson('/api/telegram/webhook', webhookMessage('/start'))->assertOk();

    Http::assertSentCount(2);
    $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();

    $welcome = $requests[0];
    expect($welcome->data()['text'])->toContain('@navoiy_elonlaruz')
        ->and(sentMarkup($welcome)['inline_keyboard'][0][0]['web_app']['url'])->toBe('https://elonlar.test/?open=create');

    $bottom = sentMarkup($requests[1]);
    expect($bottom['keyboard'][0][0]['text'])->toBe("📢 E'lon qo'yish")
        ->and($bottom['is_persistent'] ?? false)->toBeTrue();
});

test('the bottom button (in any language) answers with a button that opens the create page', function () {
    $this->postJson('/api/telegram/webhook', webhookMessage('📢 Подать объявление', 'ru'))->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => sentMarkup($request)['inline_keyboard'][0][0]['web_app']['url'] === 'https://elonlar.test/?open=create');
});
