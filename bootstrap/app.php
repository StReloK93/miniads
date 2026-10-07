<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;
// use App\Http\Middleware\TelegramAuth;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->append(TelegramAuth::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'api/telegraph/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response) {
            if (request()->is('api/*') && $response->getStatusCode() >= 500) {
                return response()->json([
                    'message' => 'Xatolik yuz berdi',
                    'code' => 'INTERNAL_SERVER_ERROR',
                ], $response->getStatusCode());
            }

            return $response;
        });
    })->create();
