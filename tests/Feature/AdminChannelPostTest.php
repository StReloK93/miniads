<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Jobs\SendBroadcastChunk;
use App\Models\Broadcast;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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
            'text' => "Yangi e'lonlar har kuni!\n<b>bepul</b> **qalin**",
            'targets' => ['channel'],
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
            // Telegram uslubidagi belgilar formatga aylanadi
            && str_contains($data['text'], '<b>qalin</b>')
            && $keyboard['inline_keyboard'][0][0]['text'] === "E'lon joylash"
            && $keyboard['inline_keyboard'][0][0]['url'] === 'https://t.me/NavoiyElonBot?startapp=create';
    });
});

test('post with photo is sent as sendPhoto and the temp file is removed', function () {
    Http::fake(['api.telegram.org/*' => Http::response(telegramOk())]);

    $this->actingAs($this->admin)
        ->post('/api/admin/channel-post', [
            'text' => 'Rasmli post',
            'targets' => ['channel'],
            'photo' => UploadedFile::fake()->image('post.png', 1200, 800),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'sendPhoto'));
    expect(glob(storage_path('app/tmp/*.jpg')))->toBeEmpty();
});

test('button needs both text and a valid link', function () {
    Http::fake();

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Salom', 'targets' => ['channel'], 'button_text' => 'Kirish'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('button_url');

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Salom', 'targets' => ['channel'], 'button_text' => 'Kirish', 'button_url' => 'javascript:alert(1)'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('button_url');

    Http::assertNothingSent();
});

test('telegram error is returned to the admin', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)]);

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Salom', 'targets' => ['channel']])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Telegram postni qabul qilmadi: Bad Request: chat not found');
});

test('regular users cannot post to the channel', function () {
    $user = User::create(['name' => 'User', 'telegram_user_id' => 2]);

    $this->actingAs($user)->postJson('/api/admin/channel-post', ['text' => 'Salom'])->assertForbidden();
});

function makeSubscriber(string $name, int $telegramId, bool $reachable = true, bool $blocked = false): User
{
    $user = User::create(['name' => $name, 'telegram_user_id' => $telegramId]);
    $user->forceFill(['bot_can_message' => $reachable, 'blocked_at' => $blocked ? now() : null])->save();

    return $user;
}

test('post to bot subscribers is queued in chunks for reachable, unblocked users only', function () {
    Queue::fake();
    makeSubscriber('Obunachi', 11);
    makeSubscriber('Ruxsatsiz', 12, reachable: false);
    makeSubscriber('Bloklangan', 13, blocked: true);

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => '**Yangilik**', 'targets' => ['bot']])
        ->assertOk();

    $broadcast = Broadcast::sole();
    expect($broadcast->total)->toBe(1)->and($broadcast->html)->toBe('<b>Yangilik</b>');
    Queue::assertPushed(SendBroadcastChunk::class, 1);
});

test('broadcast chunk sends messages, counts results and marks unreachable users', function () {
    Http::fake([
        'api.telegram.org/*' => Http::sequence()
            ->push(telegramOk())
            ->push(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403),
    ]);
    $first = makeSubscriber('Birinchi', 21);
    $second = makeSubscriber('Ikkinchi', 22);
    $broadcast = Broadcast::create(['text' => 'Salom', 'html' => 'Salom', 'total' => 2]);

    (new SendBroadcastChunk($broadcast->id, [$first->id, $second->id]))->handle(app(\App\Services\TelegramNotifier::class));

    $broadcast->refresh();
    expect($broadcast->sent)->toBe(1)
        ->and($broadcast->failed)->toBe(1)
        ->and($broadcast->finished_at)->not->toBeNull()
        ->and($second->fresh()->bot_can_message)->toBeFalse();
});

test('test post goes only to the admin', function () {
    Http::fake(['api.telegram.org/*' => Http::response(telegramOk())]);
    makeSubscriber('Obunachi', 31);

    $this->actingAs($this->admin)
        ->postJson('/api/admin/channel-post', ['text' => 'Sinov', 'test' => true])
        ->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => (string) $request->data()['chat_id'] === '1');
    expect(Broadcast::count())->toBe(0);
});

test('preview returns the same telegram html that will be sent', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/admin/post-preview', ['text' => '||sir|| va [havola](https://t.me/x)'])
        ->assertOk()
        ->assertJsonPath('html', '<tg-spoiler>sir</tg-spoiler> va <a href="https://t.me/x">havola</a>')
        ->assertJsonPath('length', 13);
});
