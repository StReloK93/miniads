<?php

use App\Http\Middleware\TelegramAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

const TEST_BOT_TOKEN = '123456:TEST_TOKEN';

/**
 * Telegram Mini App initData'ni Telegram imzolagandek tayyorlaydi (raw, URL-encoded query string).
 */
function signedInitData(array $user, ?int $authDate = null, string $token = TEST_BOT_TOKEN): string
{
    $data = [
        'auth_date' => (string) ($authDate ?? time()),
        'query_id' => 'AAHdF6IQAAAAAN0XohDhrOrc',
        'user' => json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
    ksort($data);

    $checkString = collect($data)->map(fn ($v, $k) => "$k=$v")->implode("\n");
    $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
    $data['hash'] = hash_hmac('sha256', $checkString, $secretKey);

    return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
}

beforeEach(function () {
    config(['services.telegram.bot_token' => TEST_BOT_TOKEN]);

    Route::post('/_test/telegram-auth', fn () => response()->json(request('telegram_user')))
        ->middleware(TelegramAuth::class);
});

test('valid init data passes and exposes the telegram user', function (array $user) {
    $this->postJson('/_test/telegram-auth', [], ['Authorization' => 'tma ' . signedInitData($user)])
        ->assertOk()
        ->assertJson($user);
})->with([
    'simple user' => [['id' => 1, 'first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'ali']],
    'no last name' => [['id' => 2, 'first_name' => 'Bobur']],
    'special characters' => [['id' => 3, 'first_name' => 'A&B + C', 'last_name' => "O'g'li 100%"]],
    'cyrillic' => [['id' => 4, 'first_name' => 'Шерзод']],
]);

test('tampered init data is rejected', function () {
    $initData = str_replace('Ali', 'Hacker', signedInitData(['id' => 1, 'first_name' => 'Ali']));

    $this->postJson('/_test/telegram-auth', [], ['Authorization' => 'tma ' . $initData])
        ->assertStatus(403);
});

test('init data signed with another bot token is rejected', function () {
    $initData = signedInitData(['id' => 1, 'first_name' => 'Ali'], token: '999:OTHER');

    $this->postJson('/_test/telegram-auth', [], ['Authorization' => 'tma ' . $initData])
        ->assertStatus(403);
});

test('expired init data is rejected', function () {
    $initData = signedInitData(['id' => 1, 'first_name' => 'Ali'], authDate: time() - 2 * 86400);

    $this->postJson('/_test/telegram-auth', [], ['Authorization' => 'tma ' . $initData])
        ->assertStatus(403);
});

test('missing authorization header is rejected', function () {
    $this->postJson('/_test/telegram-auth')->assertStatus(401);
});

test('sign-in endpoint works for a user without last name and username', function () {
    $initData = signedInitData(['id' => 777, 'first_name' => 'Bobur']);

    $this->postJson('/api/telegram/sign-in', [], ['Authorization' => 'tma ' . $initData])
        ->assertOk()
        ->assertJsonPath('user.name', 'Bobur')
        ->assertJsonPath('user.username', null)
        ->assertJsonStructure(['token', 'type', 'user']);
});
