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
        'POST api/products/ai-draft' => ['ai_draft', null],
        'POST api/favorites' => ['favorite_add', null],
        'POST api/saved-searches' => ['saved_search_add', null],
        'POST api/products/{id}/report' => ['report', 'id'],
        'POST api/products/{id}' => ['ad_edited', 'id'],
        // Uzaytirish ham shu: muddat yangilanadi, e'lon lenta boshiga chiqadi
        'POST api/products/{id}/activate' => ['ad_activated', 'id'],
        'POST api/products/{id}/deactivate' => ['ad_deactivated', 'id'],
        'POST api/user/change-district' => ['district_change', null],
    ];

    /**
     * Forma rad etilgan (422) so'rovlar: foydalanuvchi nimani to'ldira olmayotganini admin ko'radi.
     * ref — xato bergan maydonlar (masalan "price,images").
     */
    private const FAILED_EVENTS = [
        'POST api/products' => 'ad_failed',
        'POST api/products/{id}' => 'ad_failed',
        'POST api/products/ai-draft' => 'ai_draft_failed',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $route = $request->route();
        if (! $route) {
            return;
        }
        $key = $request->method() . ' ' . $route->uri();

        if (! $response->isSuccessful()) {
            $this->recordFailure($request, $response, $key);

            return;
        }

        $match = self::EVENTS[$key] ?? null;
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
            'district_change' => $request->input('district_id') ?: 'all',
            default => $refParam ? $route->parameter($refParam) : null,
        };

        $events = [['e' => $event, 'r' => $ref]];
        // "Matn bilan" (AI) to'ldirilgan formadan joylangan e'lon — alohida ham sanaladi
        if ($event === 'ad_created' && $request->boolean('via_ai')) {
            $events[] = ['e' => 'ad_created_ai', 'r' => $ref];
        }

        ActivityLog::record(
            $events,
            $request->user()?->id,
            $request->header('X-Session'),
            $request->header('X-Client'),
            app()->getLocale(),
        );
    }

    private function recordFailure(Request $request, Response $response, string $key): void
    {
        $event = self::FAILED_EVENTS[$key] ?? null;
        $status = $response->getStatusCode();
        if (! $event || ! in_array($status, [403, 422, 429], true)) {
            return;
        }

        $ref = (string) $status;
        if ($status === 422) {
            $errors = json_decode((string) $response->getContent(), true)['errors'] ?? [];
            // "images.0" va "images.1" — bitta "images"
            $fields = array_unique(array_map(fn ($field) => explode('.', (string) $field)[0], array_keys($errors)));
            $ref = $fields ? implode(',', $fields) : $ref;
        }

        ActivityLog::record(
            [['e' => $event, 'r' => $ref]],
            $request->user()?->id,
            $request->header('X-Session'),
            $request->header('X-Client'),
            app()->getLocale(),
        );
    }
}
