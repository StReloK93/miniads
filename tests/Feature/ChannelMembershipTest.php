<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

const MEMBER_CHANNEL = '-100777';

beforeEach(function () {
    config(['services.telegram.channels' => [MEMBER_CHANNEL], 'cache.default' => 'array']);
    // Telegram: 1001 — kanalda, 1002 — chiqib ketgan, 1003 — Telegram uni tanimaydi
    Http::fake(function (Request $request) {
        if (str_ends_with($request->url(), '/getChat')) {
            return Http::response(['ok' => true, 'result' => ['id' => MEMBER_CHANNEL, 'title' => "Navoiy e'lonlar", 'username' => 'navoiy']]);
        }
        $userId = (int) ($request->data()['user_id'] ?? 0);

        return match ($userId) {
            1001 => Http::response(['ok' => true, 'result' => ['status' => 'member']]),
            1002 => Http::response(['ok' => true, 'result' => ['status' => 'left']]),
            default => Http::response(['ok' => false, 'description' => 'Bad Request: user not found'], 400),
        };
    });
});

function memberUser(int $telegramId, bool $bot): User
{
    $user = User::create(['name' => "U{$telegramId}", 'telegram_user_id' => $telegramId]);
    $user->forceFill(['bot_can_message' => $bot])->save();

    return $user;
}

test('the sweep records channel membership and admins can filter channel / bot / both', function () {
    $both = memberUser(1001, true);
    $botOnly = memberUser(1002, true);
    $none = memberUser(1003, false);
    $unchecked = memberUser(1004, false);

    $this->artisan('users:check-channels --limit=3')->assertSuccessful();

    expect($both->fresh()->channelMemberships()->where('is_member', true)->exists())->toBeTrue()
        ->and($botOnly->fresh()->channels_checked_at)->not->toBeNull()
        ->and($none->fresh()->channelMemberships()->first()->status)->toBe('left');

    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));

    $response = $this->getJson('/api/admin/users')->assertOk();
    expect($response->json('subscription_counts'))->toMatchArray(['both' => 1, 'bot_only' => 1, 'none' => 1, 'channel_only' => 0])
        ->and($response->json('channels.0.title'))->toBe("Navoiy e'lonlar");

    $row = collect($response->json('data'))->firstWhere('id', $both->id);
    expect($row['channel_ids'])->toBe([MEMBER_CHANNEL])->and($row)->not->toHaveKey('channel_memberships');

    $ids = fn (string $filter) => collect($this->getJson("/api/admin/users?subscription={$filter}")->json('data'))->pluck('id')->all();
    expect($ids('both'))->toBe([$both->id])
        ->and($ids('bot_only'))->toBe([$botOnly->id])
        ->and($ids('none'))->toBe([$none->id])
        ->and($ids('unchecked'))->toContain($unchecked->id);
});

test('a channel join update from the webhook is recorded immediately', function () {
    $user = memberUser(1002, false);

    $this->postJson('/api/telegram/webhook', ['chat_member' => [
        'chat' => ['id' => (int) MEMBER_CHANNEL, 'type' => 'channel'],
        'from' => ['id' => 1002],
        'old_chat_member' => ['status' => 'left', 'user' => ['id' => 1002]],
        'new_chat_member' => ['status' => 'member', 'user' => ['id' => 1002]],
    ]])->assertOk();

    expect($user->channelMemberships()->where('chat_id', MEMBER_CHANNEL)->value('is_member'))->toBeTrue();
});

test('admin can re-check one user right away', function () {
    $user = memberUser(1001, false);
    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));

    $this->postJson("/api/admin/users/{$user->id}/check-channels")
        ->assertOk()
        ->assertJsonPath('channel_ids', [MEMBER_CHANNEL]);
});
