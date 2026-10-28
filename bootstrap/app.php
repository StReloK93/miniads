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
        // Ilova tili (X-Locale): server xabarlari va bazadagi nomlar (kategoriya, parametr...) shu tilda
        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
            // Faollik statistikasi: ma'noli so'rovlar javobdan keyin faylga yoziladi
            \App\Http\Middleware\TrackActivity::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/telegraph/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Kutilmagan har qanday xato (500, cron buyruqlari) — admin chatiga ogohlantirish
        $exceptions->report(function (\Throwable $e) {
            $request = app()->runningInConsole() ? null : request();
            \App\Services\ErrorAlert::send(
                get_class($e) . '@' . $e->getFile() . ':' . $e->getLine(),
                'Server xatosi: ' . class_basename($e),
                [
                    'Xabar' => $e->getMessage(),
                    'Joyi' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $e->getFile()) . ':' . $e->getLine(),
                    "So'rov" => $request ? $request->method() . ' ' . $request->path() : implode(' ', array_slice($_SERVER['argv'] ?? [], 1)),
                    'Foydalanuvchi' => $request?->user()?->id,
                ],
            );
        });

        // Laravel 401 ni logga yozmaydi. Ma'lumot yuborishda (e'lon qo'yish, tahrirlash...) 401 — foydalanuvchi
        // amalni bajara olmadi degani: sababini aniqlash uchun tafsilotlar bilan xabar beramiz
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*') || $request->isMethod('GET')) {
                return null;
            }

            $bearer = $request->bearerToken();
            $tokenId = $bearer && str_contains($bearer, '|') ? (int) explode('|', $bearer, 2)[0] : null;
            $tokenUserId = $tokenId
                ? \Laravel\Sanctum\PersonalAccessToken::whereKey($tokenId)->value('tokenable_id')
                : null;

            [$reasonCode, $reason] = match (true) {
                ! $request->headers->has('Authorization') => ['no_header', 'Authorization sarlavhasi kelmadi'],
                ! $bearer => ['not_bearer', "Bearer token emas: " . mb_substr((string) $request->header('Authorization'), 0, 10)],
                ! $tokenId => ['bad_format', "Token formati noto'g'ri"],
                ! $tokenUserId => ['missing', "Token bazada yo'q (#{$tokenId}) — o'chirilgan yoki boshqa bazaniki"],
                default => ['mismatch', "Token #{$tokenId} (user #{$tokenUserId}) mos kelmadi"],
            };

            \App\Services\ErrorAlert::send(
                "401:{$request->path()}:{$reasonCode}",
                "Foydalanuvchi ro'yxatdan o'tmagan deb rad etildi (401)",
                [
                    "So'rov" => $request->method() . ' ' . $request->path(),
                    'Sabab' => $reason,
                    'Ilova' => $request->header('X-Client'),
                    'IP' => $request->ip(),
                    'Brauzer' => $request->userAgent(),
                ],
            );

            return null;
        });

        $exceptions->respond(function (Response $response) {
            if (request()->is('api/*') && $response->getStatusCode() >= 500 && !config('app.debug')) {
                return response()->json([
                    'message' => 'Xatolik yuz berdi',
                    'code' => 'INTERNAL_SERVER_ERROR',
                ], $response->getStatusCode());
            }

            return $response;
        });
    })->create();
