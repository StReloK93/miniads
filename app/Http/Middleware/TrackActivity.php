<?php

namespace App\Http\Middleware;

use App\Services\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ma'noli API so'rovlarini (e'lon ko'rildi, kategoriya ochildi, e'lon qo'yildi...) faollik statistikasiga yozadi.
 * Ilovadan ortiqcha so'rov yo'q; yozish javob yuborilgandan keyin (terminate) — foydalanuvchi kutmaydi.
 */
class TrackActivity
{
    /** "METHOD route-uri" => [hodisa, ref sifatida olinadigan route parametri] */
    private const EVENTS = [
        'GET api/products/{id}' => ['product_view', 'id'],
        'GET api/categories/{id}/products' => ['category_open', 'id'],
        'POST api/products' => ['ad_created', null],
        'POST api/favorites' => ['favorite_add', null],
        'POST api/saved-searches' => ['saved_search_add', null],
        'POST api/products/{id}/report' => ['report', 'id'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $route = $request->route();
        if (! $route || ! $response->isSuccessful()) {
            return;
        }

        $match = self::EVENTS[$request->method() . ' ' . $route->uri()] ?? null;
        if (! $match) {
            return;
        }
        [$event, $refParam] = $match;

        // Cheksiz aylantirishdagi keyingi sahifalar — kategoriya qayta ochilgani emas
        if ($event === 'category_open' && (int) $request->query('page', 1) > 1) {
            return;
        }

        $ref = match ($event) {
            'ad_created' => $request->input('category_id'),
            default => $refParam ? $route->parameter($refParam) : null,
        };

        ActivityLog::record(
            [['e' => $event, 'r' => $ref]],
            $request->user()?->id,
            $request->header('X-Session'),
            $request->header('X-Client'),
            app()->getLocale(),
        );
    }
}
