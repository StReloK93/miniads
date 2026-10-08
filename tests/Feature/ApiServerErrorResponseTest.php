<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;

test('api server errors return a generic response without exception details when debug is false', function () {
    config(['app.debug' => false]);

    $request = Request::create('/api/testing/server-error', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $this->app->instance('request', $request);

    $response = app(ExceptionHandler::class)->render(
        $request,
        new RuntimeException('Database credentials leaked'),
    );

    expect($response->getStatusCode())->toBe(500)
        ->and(json_decode($response->getContent(), true))->toBe([
            'message' => 'Xatolik yuz berdi',
            'code' => 'INTERNAL_SERVER_ERROR',
        ])
        ->and($response->getContent())->not->toContain('Database credentials leaked');
});

test('api server errors include details when debug is true', function () {
    config(['app.debug' => true]);

    $request = Request::create('/api/testing/server-error', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $this->app->instance('request', $request);

    $response = app(ExceptionHandler::class)->render(
        $request,
        new RuntimeException('Detailed debug error for developer'),
    );

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('Detailed debug error for developer');
});

test('api client errors retain their original status code', function () {
    $request = Request::create('/api/testing/not-found', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $this->app->instance('request', $request);

    $response = app(ExceptionHandler::class)->render(
        $request,
        new Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    );

    expect($response->getStatusCode())->toBe(404);
});
