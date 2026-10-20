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

test('test auth rejects requests not originating from localhost', function (string $url, string $ip) {
    $this->app->instance('env', 'local');

    $request = Request::create($url, 'POST', server: ['REMOTE_ADDR' => $ip]);

    expect(fn () => app(AuthService::class)->testAuth($request))
        ->toThrow(HttpException::class);
})->with([
    ['http://public.example/api/test-auth', '127.0.0.1'],
    ['http://localhost/api/test-auth', '203.0.113.10'],
]);
