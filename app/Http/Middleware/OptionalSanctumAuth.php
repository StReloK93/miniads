<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ochiq (mehmonlar uchun) route'lar: login talab qilinmaydi, lekin token yuborilgan bo'lsa
 * auth()->user() o'sha foydalanuvchini qaytaradi (masalan, is_favorite uchun).
 */
class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
