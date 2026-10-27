<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ilova tilni X-Locale sarlavhasida yuboradi (Accept-Language emas: uni brauzer o'zi qo'yadi va
 * admin panel ham rus/ingliz tiliga o'tib ketardi — admin doim o'zbekcha, sarlavhasiz).
 * Kirgan foydalanuvchining tili saqlanadi — bot xabarlari shu tilda yuboriladi.
 */
class SetLocale
{
    public const LOCALES = ['uz', 'ru', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('X-Locale');
        if (! in_array($locale, self::LOCALES, true)) {
            $locale = config('app.locale');
        }
        app()->setLocale($locale);

        if ($request->hasHeader('X-Locale')) {
            $user = $request->user('sanctum');
            if ($user && $user->language !== $locale) {
                $user->forceFill(['language' => $locale])->saveQuietly();
            }
        }

        return $next($request);
    }
}
