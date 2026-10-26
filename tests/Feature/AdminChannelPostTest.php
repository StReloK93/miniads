<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.telegram.channels' => ['-100111', '-100222'],
        'telegraph.bots' => [],
    ]);
    $this->admin = User::create(['name' => 'Admin', 'telegram_user_id' => 1, 'role' => 'admin']);
});

function telegramOk(int $messageId = 10): array
{
    return ['ok' => true, 'result' => ['message_id' => $messageId]];
}

test('admin sends a text post with a custom button to every channel', function () {
    Http::fake(['api.telegram.org/*' => Http::response(telegramOk())]);

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', [
            'text' => "Yangi e'lonlar har kuni!\n<b>bepul</b>",
            'button_text' => "E'lon joylash",
            'button_url' => 'https://t.me/NavoiyElonBot?startapp=create',
        ])
        ->assertOk()
        ->assertJsonPath('sent.-100111', 10);

    Http::assertSentCount(2);
    Http::assertSent(function ($request) {
        $data = $request->data();
        $markup = $data['reply_markup'] ?? [];
        $keyboard = is_string($markup) ? json_decode($markup, true) : $markup;

        return str_contains($request->url(), 'sendMessage')
            // HTML belgilar ekranlanadi — admin matni Telegram formatini buzmaydi
            && str_contains($data['text'], '&lt;b&gt;bepul&lt;/b&gt;')
            && $keyboard['inline_keyboard'][0][0]['text'] === "E'lon joylash"
            && $keyboard['inline_keyboard'][0][0]['url'] === 'https://t.me/NavoiyElonBot?startapp=create';
    });
});

test('post with photo is sent as sendPhoto and the temp file is removed', function () {
    Http::fake(['api.telegram.org/*' => Http::response(telegramOk())]);

    $this->actingAs($this->admin)
        ->post('/api/admin/channel-post', [
            'text' => 'Rasmli post',
            'photo' => UploadedFile::fake()->image('post.png', 1200, 800),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'sendPhoto'));
    expect(glob(storage_path('app/tmp/*.jpg')))->toBeEmpty();
});

test('button needs both text and a valid link', function () {
    Http::fake();

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Salom', 'button_text' => 'Kirish'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('button_url');

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Salom', 'button_text' => 'Kirish', 'button_url' => 'javascript:alert(1)'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('button_url');

    Http::assertNothingSent();
});

test('telegram error is returned to the admin', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)]);

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Salom'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Telegram postni qabul qilmadi: Bad Request: chat not found');
});

test('regular users cannot post to the channel', function () {
    $user = User::create(['name' => 'User', 'telegram_user_id' => 2]);

    $this->actingAs($user)->postJson('/api/admin/channel-post', ['text' => 'Salom'])->assertForbidden();
});
