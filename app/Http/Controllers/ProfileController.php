<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\SearchLog;
use App\Support\UzbekText;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** Profil sahifasi uchun hammasi bitta so'rovda: statistika, sozlamalar, kuzatuvlar */
    public function show(Request $request)
    {
        $user = $request->user();
        $products = Product::query()->setEagerLoads([])->where('user_id', $user->id);

        return response()->json([
            'stats' => [
                'active' => (clone $products)->active()->count(),
                'total' => (clone $products)->count(),
                'views' => (int) (clone $products)->sum('views_count'),
                'favorites' => Favorite::where('user_id', $user->id)->count(),
            ],
            'saved_searches' => $user->savedSearches()->latest()->get(),
            'support_username' => config('services.telegram.support_username'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'notify_saved_searches' => ['sometimes', 'boolean'],
            'notify_expiry' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $user->fill($validated)->save();

        return $user->load('activeDistrict');
    }

    /** Ilovadagi "botga yozishga ruxsat" oynasi natijasi (web_app_request_write_access) */
    public function writeAccess(Request $request)
    {
        $validated = $request->validate(['allowed' => ['required', 'boolean']]);

        $user = $request->user();
        $user->forceFill(['bot_can_message' => $validated['allowed']])->save();

        return $user->load('activeDistrict');
    }

    /**
     * Qidiruv "o'rnashgan"da (foydalanuvchi yozishni to'xtatgan yoki Enter bosgan) — admin statistikasi uchun.
     * Har bir harf bo'yicha emas: ilova o'zi kutib yuboradi.
     */
    public function logSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:120'],
            'results_count' => ['required', 'integer', 'min:0'],
        ]);

        $query = UzbekText::normalize($validated['query']);
        if (mb_strlen($query) >= 2) {
            SearchLog::create([
                'query' => mb_substr($query, 0, 120),
                'results_count' => $validated['results_count'],
                'user_id' => $request->user()?->id,
            ]);
        }

        return response()->noContent();
    }
}
