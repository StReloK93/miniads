<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLog;
use App\Services\ProductService;
use App\Services\TelegramProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalProducts = Product::count();
        $activeProducts = Product::active()->count();

        // Hududlar / tumanlar bo'yicha e'lonlar taqsimoti
        $districtsBreakdown = DB::table('products')
            ->leftJoin('districts', 'products.district_id', '=', 'districts.id')
            ->whereNull('products.deleted_at')
            ->selectRaw("COALESCE(districts.name, 'Navoiy viloyati') as name, COUNT(products.id) as count")
            // id bo'yicha: nomi bir xil ikki hudud bitta qatorga qo'shilib ketmasin
            ->groupBy('districts.id', 'districts.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'count' => (int) $item->count,
                'percentage' => $totalProducts > 0 ? round(($item->count / $totalProducts) * 100, 1) : 0,
            ]);

        // Kategoriyalar bo'yicha taqsimot
        $categoriesBreakdown = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('products.deleted_at')
            ->selectRaw('categories.id, categories.name, COUNT(products.id) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'count' => (int) $item->count,
                'percentage' => $totalProducts > 0 ? round(($item->count / $totalProducts) * 100, 1) : 0,
            ]);

        // Oxirgi 14 kunlik e'lonlar dinamikasi (kunlar bo'yicha timeline)
        $rawDaily = DB::table('products')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->all();

        // Carbon format('w'): 0 = yakshanba
        $dayNames = [
            1 => ['Du', 'Dushanba'],
            2 => ['Se', 'Seshanba'],
            3 => ['Ch', 'Chorshanba'],
            4 => ['Pa', 'Payshanba'],
            5 => ['Ju', 'Juma'],
            6 => ['Sh', 'Shanba'],
            0 => ['Ya', 'Yakshanba'],
        ];

        $dailyAds = [];
        // Haftalik taqsimot ham shu 14 kundan: grafik va "eng faol kun" bir davrni ko'rsatsin
        $dowCounts = array_fill_keys(array_keys($dayNames), 0);

        for ($i = 13; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateKey = $dateObj->toDateString();
            $w = (int) $dateObj->format('w');
            $count = (int) ($rawDaily[$dateKey] ?? 0);

            $dailyAds[] = [
                'date' => $dateKey,
                'day_label' => $dateObj->format('d.m'),
                'day_name' => $dayNames[$w][0],
                'count' => $count,
            ];
            $dowCounts[$w] += $count;
        }

        $weeklyDistribution = [];
        $maxDowCount = 0;
        $busiestDay = null;
        foreach ($dayNames as $w => [, $dayName]) {
            $weeklyDistribution[] = ['day' => $dayName, 'count' => $dowCounts[$w]];
            if ($dowCounts[$w] > $maxDowCount) {
                $maxDowCount = $dowCounts[$w];
                $busiestDay = $dayName;
            }
        }

        // Foydalanuvchilar faolligi: token har so'rovda last_used_at'ni yangilaydi
        $activeUsersSince = fn (Carbon $since) => DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('last_used_at', '>=', $since)
            ->distinct('tokenable_id')
            ->count('tokenable_id');

        $activeUsersToday = $activeUsersSince(now()->startOfDay());
        $activeUsersWeek = $activeUsersSince(now()->subDays(7));

        $newUsersWeek = User::where('created_at', '>=', now()->subDays(7))->count();

        // So'nggi faol foydalanuvchilar (hech kirmaganlar oxirida)
        $recentUsers = User::query()
            ->with('activeDistrict:id,name')
            ->withCount('products')
            ->addSelect([
                'users.*',
                'last_active_at' => DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereColumn('tokenable_id', 'users.id')
                    ->selectRaw('MAX(last_used_at)'),
            ])
            ->orderByRaw('last_active_at IS NULL, last_active_at DESC, users.created_at DESC')
            ->limit(6)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'telegram_user_id' => $user->telegram_user_id,
                'role' => $user->role,
                'products_count' => $user->products_count,
                'district_name' => $user->activeDistrict?->name,
                'created_at' => $user->created_at?->toISOString(),
                'last_active_at' => $this->lastActiveAt($user),
            ]);

        return response()->json([
            'stats' => [
                'products' => $totalProducts,
                'active_products' => $activeProducts,
                'expired_products' => Product::passive()->count(),
                'deleted_products' => Product::onlyTrashed()->count(),
                'total_views' => (int) Product::sum('views_count'),
                'users' => User::count(),
                'new_users_week' => $newUsersWeek,
                'active_users_today' => $activeUsersToday,
                'active_users_week' => $activeUsersWeek,
                'admins' => User::where('role', 'admin')->count(),
                'categories' => Category::count(),
                'busiest_day' => $busiestDay,
                // Shikoyat qilingan (hali ko'rilmagan) e'lonlar soni — bosh sahifadagi ogohlantirish uchun
                'reported_products' => Product::whereHas('openReports')->count(),
                'blocked_users' => User::whereNotNull('blocked_at')->count(),
            ],
            'districts_breakdown' => $districtsBreakdown,
            'categories_breakdown' => $categoriesBreakdown,
            'daily_ads' => $dailyAds,
            'weekly_distribution' => $weeklyDistribution,
            'recent_users' => $recentUsers,
            'recent_products' => Product::query()
                ->with(['user:id,name,username', 'category:id,name', 'district:id,name'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Product $product) => [...$product->toArray(), 'is_active' => $this->isActive($product)]),
        ]);
    }

    public function products(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'deleted', 'reported', 'all'])],
            'is_bot' => ['nullable', Rule::in(['all', 'bot', 'user'])],
            'user_id' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['new', 'views', 'reports'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'all';
        $sort = $filters['sort'] ?? ($status === 'reported' ? 'reports' : 'new');

        // Holat chiplaridagi sonlar uchun: qidiruv/manba/muallif filtrlari hisobga olinadi, holat — yo'q
        $base = fn () => Product::query()
            ->withTrashed()
            ->when(($filters['is_bot'] ?? 'all') === 'bot', fn ($query) => $query->where('is_bot', true))
            ->when(($filters['is_bot'] ?? 'all') === 'user', fn ($query) => $query->where('is_bot', false))
            ->when(filled($filters['user_id'] ?? null), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = ltrim($filters['search'], '#');
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                    if (ctype_digit($search)) {
                        $query->orWhere('products.id', (int) $search);
                    }
                });
            });

        $applyStatus = fn ($query, string $status) => match ($status) {
            'deleted' => $query->onlyTrashed(),
            'active' => $query->whereNull('deleted_at')->active(),
            'expired' => $query->whereNull('deleted_at')->passive(),
            'reported' => $query->whereNull('deleted_at')->whereHas('openReports'),
            default => $query,
        };

        $query = $applyStatus($base(), $status)
            ->with([
                'user:id,name,username,telegram_user_id,blocked_at',
                'category:id,name',
                'district:id,name',
                'images:id,product_id,src,thumb_src',
                'openReports' => fn ($query) => $query->with('user:id,name,username')->latest(),
            ])
            ->withCount(['favorites', 'openReports as open_reports_count'])
            ->when($status === 'all', fn ($query) => $query->orderByRaw('deleted_at IS NULL DESC'));

        match ($sort) {
            'views' => $query->orderByDesc('views_count'),
            'reports' => $query->orderByDesc('open_reports_count'),
            default => null,
        };
        $query->latest('products.id');

        $counts = collect(['all', 'active', 'expired', 'reported', 'deleted'])
            ->mapWithKeys(fn (string $key) => [$key => $applyStatus($base(), $key)->count()]);

        $page = $query->paginate($filters['per_page'] ?? 20)
            ->through(fn (Product $product) => [
                // expires_at modelda yashirin (ilova uchun); admin uchun kerak
                ...$product->makeVisible('expires_at')->toArray(),
                // Xom "Y-m-d H:i:s" iOS Safari'da o'qilmaydi — ISO formatda beramiz
                'expires_at' => $product->expires_at ? Carbon::parse($product->expires_at)->toISOString() : null,
                'is_active' => $this->isActive($product),
            ]);

        return response()->json([...$page->toArray(), 'counts' => $counts]);
    }

    /** Arxivdagi e'lonni butunlay o'chirish (rasmlari bilan) */
    public function forceDeleteProduct(int $id, ProductService $productService, TelegramProductService $telegram)
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        try {
            $telegram->deleteFromChannels($product);
        } catch (\Throwable $e) {
            Log::warning("Kanal postini o'chirib bo'lmadi: {$e->getMessage()}", ['product_id' => $id]);
        }
        $productService->forceDelete($product);

        return response()->json(['message' => "E'lon butunlay o'chirildi."]);
    }

    /**
     * Ommaviy amallar: belgilangan e'lonlarni birdaniga arxivlash, tiklash, faollashtirish, to'xtatish, shikoyatlarni yopish.
     * Har biri yakka amal bilan bir xil ishlaydi (arxivlashda kanal posti o'chadi, shikoyatlar yopiladi).
     */
    public function bulkProducts(Request $request, TelegramProductService $telegram)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['archive', 'restore', 'activate', 'deactivate', 'resolve'])],
        ]);
        $ids = $validated['ids'];

        $done = 0;
        switch ($validated['action']) {
            case 'archive':
                Product::with('category')->whereIn('id', $ids)->get()->each(function (Product $product) use ($telegram, &$done): void {
                    $product->delete();
                    $product->openReports()->update(['resolved_at' => now()]);
                    try {
                        $telegram->deleteFromChannels($product);
                    } catch (\Throwable $e) {
                        Log::warning("Kanal postini o'chirib bo'lmadi: {$e->getMessage()}", ['product_id' => $product->id]);
                    }
                    $done++;
                });
                break;
            case 'restore':
                $done = Product::onlyTrashed()->whereIn('id', $ids)->restore();
                break;
            case 'activate':
                Product::with('category')->whereIn('id', $ids)->get()->each(function (Product $product) use (&$done): void {
                    $product->forceFill([
                        'expires_at' => now()->addDays($product->category?->listing_duration_days ?: 30),
                        'published_at' => now(),
                    ])->save();
                    $done++;
                });
                break;
            case 'deactivate':
                $done = Product::whereIn('id', $ids)->update(['expires_at' => now()->subMinute()]);
                break;
            case 'resolve':
                $done = ProductReport::whereIn('product_id', $ids)->whereNull('resolved_at')->distinct('product_id')->count('product_id');
                ProductReport::whereIn('product_id', $ids)->whereNull('resolved_at')->update(['resolved_at' => now()]);
                break;
        }

        return response()->json(['message' => "{$done} ta e'lon yangilandi."]);
    }

    /**
     * Qidiruv statistikasi: eng ko'p qidirilgan so'zlar va natija bermaganlari (talab bor, e'lon yo'q).
     * Bir so'rovni bir foydalanuvchi ko'p marta yozsa ham "odamlar soni" alohida ko'rinadi.
     */
    public function searchStats(Request $request)
    {
        $days = (int) ($request->validate(['days' => ['nullable', 'integer', Rule::in([1, 7, 30])]])['days'] ?? 7);
        $since = now()->subDays($days);

        $aggregate = fn ($query) => $query
            ->where('created_at', '>=', $since)
            ->selectRaw('query, COUNT(*) as searches, COUNT(DISTINCT user_id) as users, MAX(created_at) as last_at')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(30)
            ->get();

        return response()->json([
            'days' => $days,
            'total' => DB::table('search_logs')->where('created_at', '>=', $since)->count(),
            'top' => $aggregate(DB::table('search_logs')->selectRaw('ROUND(AVG(results_count)) as avg_results')),
            // So'nggi marta ham natija bermagan so'rovlar
            'not_found' => $aggregate(DB::table('search_logs')->where('results_count', 0)),
        ]);
    }

    /**
     * Foydalanuvchilar faolligi (activity_stats). Bugungi ma'lumot sahifa ochilganda yangilanadi.
     * "kishi" har kun alohida sanaladi: ko'p kunlik oraliqda — kunlik noyob tashrifchilar yig'indisi.
     */
    public function activity(Request $request)
    {
        $days = (int) ($request->validate(['days' => ['nullable', 'integer', Rule::in([1, 7, 30])]])['days'] ?? 7);
        $since = now()->subDays($days - 1)->toDateString();

        ActivityLog::aggregate(now()->toDateString());

        $base = fn () => DB::table('activity_stats')->where('date', '>=', $since);
        $grouped = fn (string $event, int $limit = 10) => $base()
            ->where('event', $event)
            ->selectRaw('ref, SUM(hits) as hits, SUM(users) as users')
            ->groupBy('ref')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['ref' => $row->ref, 'hits' => (int) $row->hits, 'users' => (int) $row->users]);
        $hits = fn (string $event, ?string $ref = null) => (int) $base()->where('event', $event)
            ->when($ref !== null, fn ($q) => $q->where('ref', $ref))->sum('hits');
        $users = fn (string $event, ?string $ref = null) => (int) $base()->where('event', $event)
            ->when($ref !== null, fn ($q) => $q->where('ref', $ref))->sum('users');

        $daily = $base()->where('event', 'visitors')->orderBy('date')->get(['date', 'hits', 'users'])
            ->map(fn ($row) => ['date' => (string) $row->date, 'users' => (int) $row->users, 'hits' => (int) $row->hits]);

        $categories = $grouped('category_open');
        $categoryNames = Category::withTrashed()->whereIn('id', $categories->pluck('ref'))->pluck('name', 'id');
        $products = $grouped('product_view');
        $productTitles = Product::withTrashed()->whereIn('id', $products->pluck('ref'))->pluck('title', 'id');
        $visitors = $users('visitors');

        return response()->json([
            'days' => $days,
            'summary' => [
                'visitors' => $visitors,
                'avg_daily' => $daily->count() ? (int) round($visitors / $daily->count()) : 0,
                'registered' => $users('visitors_registered'),
                'app_opens' => $hits('app_open'),
                'page_views' => $hits('page'),
                'product_views' => $hits('product_view'),
                'contacts' => $hits('contact_phone') + $hits('contact_telegram'),
                'favorites' => $hits('favorite_add'),
                'saved_searches' => $hits('saved_search_add'),
                'reports' => $hits('report'),
            ],
            // E'lon qo'yish formasini ochganlar va oxiriga yetkazganlar
            'funnel' => [
                'started' => $users('page', 'create-product'),
                'created' => $users('ad_created'),
                'ads' => $hits('ad_created'),
            ],
            'daily' => $daily,
            'pages' => $grouped('page', 15),
            'categories' => $categories->map(fn ($row) => [...$row, 'label' => $categoryNames[$row['ref']] ?? "#{$row['ref']}"]),
            'products' => $products->map(fn ($row) => [...$row, 'label' => $productTitles[$row['ref']] ?? "#{$row['ref']}"]),
            'contacts' => [
                'phone' => $hits('contact_phone'),
                'telegram' => $hits('contact_telegram'),
            ],
            'sources' => $grouped('app_open'),
            'clients' => $grouped('client'),
            'locales' => $grouped('locale'),
        ]);
    }

    public function moderationSettings()
    {
        return response()->json([
            'banned_words' => Setting::getValue(Setting::BANNED_WORDS, ''),
        ]);
    }

    public function updateModerationSettings(Request $request)
    {
        $validated = $request->validate(['banned_words' => ['nullable', 'string', 'max:10000']]);
        Setting::setValue(Setting::BANNED_WORDS, trim((string) ($validated['banned_words'] ?? '')));

        return response()->json([
            'message' => "Saqlandi. Yangi e'lonlar shu ro'yxat bo'yicha tekshiriladi.",
            'count' => count(Setting::bannedWords()),
        ]);
    }

    /** Shikoyatlar ko'rib chiqildi: e'lon joyida qoladi, shikoyatlar ro'yxatdan chiqadi */
    public function resolveReports(int $id)
    {
        ProductReport::where('product_id', $id)->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return response()->json(['message' => 'Shikoyatlar yopildi.']);
    }

    public function updateProductStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $product = Product::withTrashed()->findOrFail($id);
        abort_if($product->trashed(), 422, "O'chirilgan e'lonni avval tiklang.");

        if ($validated['status'] === 'active') {
            $product->loadMissing('category');
            // published_at $fillable'da yo'q — update() uni jim tashlab yuborardi, e'lon lenta boshiga chiqmasdi
            $product->forceFill([
                'expires_at' => now()->addDays($product->category?->listing_duration_days ?: 30),
                'published_at' => now(),
            ])->save();
        } else {
            $product->update(['expires_at' => now()->subMinute()]);
        }

        return response()->json(['message' => "E'lon holati yangilandi."]);
    }

    public function deleteProduct(int $id, TelegramProductService $telegram)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        // Arxivlash — moderatsiya qarori: shikoyatlar yopiladi, kanaldagi post o'chadi (havola o'lik bo'lib qolmasin)
        $product->openReports()->update(['resolved_at' => now()]);
        try {
            $telegram->deleteFromChannels($product);
        } catch (\Throwable $e) {
            Log::warning("Kanal postini o'chirib bo'lmadi: {$e->getMessage()}", ['product_id' => $id]);
        }

        return response()->json(['message' => "E'lon arxivga ko'chirildi."]);
    }

    public function restoreProduct(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return response()->json(['message' => "E'lon tiklandi."]);
    }

    public function users(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(['user', 'admin', 'blocked'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return User::query()
            ->with('activeDistrict:id,name')
            ->withCount(['products', 'products as active_products_count' => fn ($query) => $query->active()])
            ->addSelect([
                'users.*',
                'last_active_at' => DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereColumn('tokenable_id', 'users.id')
                    ->selectRaw('MAX(last_used_at)'),
            ])
            ->when(($filters['role'] ?? null) === 'blocked', fn ($query) => $query->whereNotNull('blocked_at'))
            ->when(in_array($filters['role'] ?? null, ['user', 'admin'], true), fn ($query) => $query->where('role', $filters['role']))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('telegram_user_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->through(fn (User $user) => [
                ...$user->toArray(),
                'last_active_at' => $this->lastActiveAt($user),
            ]);
    }

    public function updateUserRole(Request $request, int $id)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);
        $user = User::findOrFail($id);

        DB::transaction(function () use ($request, $user, $validated): void {
            if ($user->role === 'admin' && $validated['role'] === 'user') {
                abort_if(
                    (int) $request->user()->id === (int) $user->id,
                    422,
                    "O'zingizning admin huquqingizni olib tashlay olmaysiz.",
                );

                $adminCount = User::query()->where('role', 'admin')->lockForUpdate()->count();
                abort_if($adminCount <= 1, 422, "Oxirgi admin huquqini olib tashlab bo'lmaydi.");
            }

            $user->update(['role' => $validated['role']]);
        });

        return response()->json(['message' => 'Foydalanuvchi roli yangilandi.']);
    }

    /**
     * Bloklangan foydalanuvchi ilovadan foydalanaveradi (ko'rish, qidirish), lekin e'lon joylay olmaydi.
     * Ixtiyoriy: hozirgi e'lonlari ham arxivga ko'chadi (keyin tiklash mumkin).
     */
    public function blockUser(Request $request, int $id)
    {
        $validated = $request->validate([
            'archive_products' => ['nullable', 'boolean'],
        ]);
        $user = User::findOrFail($id);

        abort_if((int) $request->user()->id === (int) $user->id, 422, "O'zingizni bloklay olmaysiz.");
        abort_if($user->role === 'admin', 422, "Adminni bloklab bo'lmaydi — avval huquqini oling.");

        $archived = 0;
        DB::transaction(function () use ($user, $validated, &$archived): void {
            $user->forceFill(['blocked_at' => now()])->save();

            if ($validated['archive_products'] ?? false) {
                $archived = $user->products()->count();
                $user->products()->each(fn (Product $product) => $product->delete());
            }
        });

        return response()->json([
            'message' => $archived ? "Foydalanuvchi bloklandi, {$archived} ta e'loni arxivlandi." : 'Foydalanuvchi bloklandi.',
        ]);
    }

    public function unblockUser(int $id)
    {
        User::findOrFail($id)->forceFill(['blocked_at' => null])->save();

        return response()->json(['message' => 'Foydalanuvchi blokdan chiqarildi.']);
    }

    public function getBotSettings()
    {
        $setting = \App\Models\BotSetting::current();
        $totalBotProducts = Product::where('is_bot', true)->count();
        $totalUserProducts = Product::where('is_bot', false)->count();

        return response()->json([
            'setting' => $setting,
            'stats' => [
                'total_bot_products' => $totalBotProducts,
                'total_user_products' => $totalUserProducts,
                'today_imported_count' => $setting->today_imported_count,
                'daily_limit' => $setting->daily_limit,
                'hourly_limit' => $setting->hourly_limit,
                'last_run_at' => $setting->last_run_at?->diffForHumans() ?: 'Hali ishga tushmagan',
                'ai_usage' => \App\Services\Bot\AdAiParserService::todayUsage(),
                'ai_model' => match ($setting->ai_provider) {
                    'gemini' => config('services.gemini.model'),
                    'openai' => config('services.openai.model'),
                    default => null,
                },
            ],
        ]);
    }

    public function updateBotSettings(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'hourly_limit' => ['required', 'integer', 'min:1', 'max:50'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'channels' => ['nullable', 'string', 'max:2000'],
            'telegram_post_enabled' => ['required', 'boolean'],
            'telegram_start_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'telegram_end_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'ai_provider' => ['required', 'string', Rule::in(['gemini', 'openai', 'regex'])],
            'ai_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = \App\Models\BotSetting::current();
        $setting->update($validated);

        return response()->json([
            'message' => "Bot sozlamalari muvaffaqiyatli saqlandi.",
            'setting' => $setting->fresh(),
        ]);
    }

    public function runBotImport(\App\Services\Bot\BotAdImportService $service)
    {
        $setting = \App\Models\BotSetting::current();
        // Har doim admin panelda ko'rsatilgan limit (masalan 10 ta) miqdorida kiritishni maqsad qiladi
        $result = $service->importBatch(customTarget: $setting->hourly_limit, force: true);

        return response()->json([
            'message' => $result['message'] ?? 'Import bajarildi.',
            'result' => $result,
        ]);
    }

    /**
     * Kanal posti formasi uchun standart qiymatlar.
     */
    public function channelPostDefaults()
    {
        return response()->json([
            'channels' => \App\Services\TelegramProductService::channels(),
            'button_text' => "E'lon joylash",
            'button_url' => rtrim(config('services.telegram.mini_app_url'), '/') . '?startapp=create',
        ]);
    }

    /**
     * Kanal(lar)ga ixtiyoriy post: matn, ixtiyoriy rasm va ixtiyoriy tugma (matni va havolasi admin tomonidan).
     */
    public function sendChannelPost(Request $request, \App\Services\ChannelPostService $service)
    {
        $hasPhoto = $request->hasFile('photo');

        $validated = $request->validate([
            // Rasmli postda Telegram izoh (caption) uzunligi 1024 belgi bilan cheklangan
            'text' => ['required', 'string', 'max:' . ($hasPhoto ? 1024 : 4096)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'button_text' => ['nullable', 'required_with:button_url', 'string', 'max:64'],
            'button_url' => ['nullable', 'required_with:button_text', 'string', 'max:512', 'regex:/^(https?:\/\/|tg:\/\/)\S+$/i'],
        ], [
            'button_url.regex' => "Havola https://, http:// yoki tg:// bilan boshlanishi kerak.",
        ]);

        try {
            $sent = $service->send(
                $validated['text'],
                $request->file('photo'),
                $validated['button_text'] ?? null,
                $validated['button_url'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => "Telegram postni qabul qilmadi: {$e->getMessage()}"], 422);
        }

        return response()->json([
            'message' => count($sent) > 1 ? count($sent) . " ta kanalga yuborildi." : "Post kanalga yuborildi.",
            'sent' => $sent,
        ]);
    }

    /**
     * MAX(last_used_at) — xom "Y-m-d H:i:s" satr (vaqt zonasisiz, iOS Safari uni o'qimaydi).
     * Hech kirmagan bo'lsa null: updated_at'ga almashtirsak "hozirgina faol" deb ko'rinardi.
     */
    private function lastActiveAt(User $user): ?string
    {
        return $user->last_active_at ? Carbon::parse($user->last_active_at)->toISOString() : null;
    }

    private function isActive(Product $product): bool
    {
        return ! $product->trashed()
            && filled($product->expires_at)
            && Carbon::parse($product->expires_at)->isFuture();
    }
}
