<?php

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

test('localhost test auth creates and logs in a local developer', function () {
    $this->app->instance('env', 'local');

    $request = Request::create(
        'http://localhost/api/test-auth',
        'POST',
        server: ['REMOTE_ADDR' => '127.0.0.1'],
    );

    $response = app(AuthService::class)->testAuth($request);
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data['token'])->toBeString()
        ->and(User::where('username', 'local-dev')->value('role'))->toBe('admin');
});

test('a second local login does not sign out the first browser; week-old unused tokens are pruned', function () {
    $this->app->instance('env', 'local');
    $login = fn () => app(AuthService::class)->testAuth(
        Request::create('http://localhost/api/test-auth', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']),
    )->getData(true)['token'];

    $first = $login();
    $user = User::where('username', 'local-dev')->firstOrFail();
    $stale = $user->createToken('old');
    $stale->accessToken->forceFill(['created_at' => now()->subWeeks(2), 'last_used_at' => now()->subWeeks(2)])->save();

    $login();

    $ids = $user->tokens()->pluck('id');
    expect($ids)->toContain((int) explode('|', $first)[0])
        ->and($ids)->not->toContain($stale->accessToken->id)
        ->and($ids)->toHaveCount(2);
});

test('test auth rejects requests not originating from localhost', function (string $url, string $ip) {
    $this->app->instance('env', 'local');

    $request = Request::create($url, 'POST', server: ['REMOTE_ADDR' => $ip]);

    expect(fn () => app(AuthService::class)->testAuth($request))
        ->toThrow(HttpException::class);
})->with([
    ['http://public.example/api/test-auth', '127.0.0.1'],
    ['http://localhost/api/test-auth', '203.0.113.10'],
]);
