<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLog;
use App\Services\ChannelMembershipService;
use App\Services\ProductService;
use App\Jobs\SyncProductToTelegram;
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
            // ai — Telegram assistent kanallardan AI bilan qo'ygan e'lonlar (is_bot), qolgani odamlarniki
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as ai')
            ->groupBy('date')
            ->get()
            ->keyBy('date')
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
            $count = (int) ($rawDaily[$dateKey]->count ?? 0);
            $ai = (int) ($rawDaily[$dateKey]->ai ?? 0);

            $dailyAds[] = [
                'date' => $dateKey,
                'day_label' => $dateObj->format('d.m'),
                'day_name' => $dayNames[$w][0],
                'count' => $count,
                'people' => $count - $ai,
                'ai' => $ai,
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
                // Egasi "Sotildi" deb to'xtatgan e'lonlar (eskilari products:prune bilan o'chadi — asosiysi 30 kunlik son)
                'sold_products' => Product::withTrashed()->whereNotNull('sold_at')->count(),
                'sold_products_month' => Product::withTrashed()->where('sold_at', '>=', now()->subDays(30))->count(),
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
                // Bot orqali kirgan / botni ishga tushirgan: bot ularga xabar yubora oladi
                'bot_reachable' => self::userSegments()['bot'](User::query())->count(),
                'bot_reachable_active' => self::userSegments()['bot'](User::query())->whereNull('blocked_at')->count(),
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
            // Kategoriya — ichki bo'limlari bilan; hudud: id yoki "none" (butun viloyat, district_id = null)
            'category_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'regex:/^(\d+|none)$/'],
            'sort' => ['nullable', Rule::in(['new', 'views', 'reports'])],
            // Bir xil telefon raqami bilan bir nechta e'lon (takror kiritilgan)
            'duplicates' => ['nullable', 'boolean'],
            // Shu kuni joylangan e'lonlar (bosh sahifadagi "Hammasi" havolasi)
            'date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'all';
        $day = filled($filters['date'] ?? null) ? Carbon::parse($filters['date']) : null;
        $duplicates = (bool) ($filters['duplicates'] ?? false);
        $phoneKey = self::PHONE_KEY_SQL;
        $sort = $filters['sort'] ?? ($status === 'reported' ? 'reports' : 'new');
        $categoryIds = filled($filters['category_id'] ?? null) ? Category::subtreeIds((int) $filters['category_id']) : null;
        $district = $filters['district_id'] ?? null;

        // Holat chiplaridagi sonlar uchun: qidiruv/manba/muallif/kategoriya/hudud filtrlari hisobga olinadi, holat — yo'q
        $base = fn () => Product::query()
            ->withTrashed()
            ->when($day !== null, fn ($query) => $query->whereBetween('products.created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]))
            ->when(($filters['is_bot'] ?? 'all') === 'bot', fn ($query) => $query->where('is_bot', true))
            ->when(($filters['is_bot'] ?? 'all') === 'user', fn ($query) => $query->where('is_bot', false))
            ->when(filled($filters['user_id'] ?? null), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($categoryIds !== null, fn ($query) => $query->whereIn('category_id', $categoryIds))
            ->when($district === 'none', fn ($query) => $query->whereNull('district_id'))
            ->when($district !== null && $district !== 'none', fn ($query) => $query->where('district_id', (int) $district))
            ->when($duplicates, fn ($query) => $query->whereIn(DB::raw($phoneKey), $this->duplicatePhoneKeys()))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters, $phoneKey): void {
                $search = ltrim(trim($filters['search']), '#@');
                $phone = self::phoneSearchKey($search);
                $query->where(function ($query) use ($search, $phone, $phoneKey): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                    // Telefon har qanday ko'rinishda: +998 93 123 45 67, 931234567, 93-123-45-67 — bitta raqam
                    if ($phone !== null) {
                        $query->orWhereRaw("{$phoneKey} LIKE ?", ["%{$phone}%"])
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->whereRaw(self::PHONE_KEY_SQL . ' LIKE ?', ["%{$phone}%"]));
                        // Bot e'lonlarida raqam tavsif ichida (93-123-45-67 ko'rinishiga keltirilgan)
                        if (strlen($phone) === 9) {
                            $query->orWhere('description', 'like', '%' . \App\Support\Phone::format($phone) . '%');
                        }
                    }
                    if (ctype_digit($search) && strlen($search) <= 9) {
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
            ->when($status === 'all', fn ($query) => $query->orderByRaw('deleted_at IS NULL DESC'))
            // Takroriylar ro'yxatida bir raqamli e'lonlar yonma-yon turadi
            ->when($duplicates, fn ($query) => $query->orderByRaw($phoneKey));

        match ($sort) {
            'views' => $query->orderByDesc('views_count'),
            'reports' => $query->orderByDesc('open_reports_count'),
            default => null,
        };
        $query->latest('products.id');

        $counts = collect(['all', 'active', 'expired', 'reported', 'deleted'])
            ->mapWithKeys(fn (string $key) => [$key => $applyStatus($base(), $key)->count()]);
        // Butun bazada: raqami boshqa e'londa ham uchraydigan e'lonlar (arxivdagilarsiz)
        $counts['duplicates'] = Product::query()->whereIn(DB::raw($phoneKey), $this->duplicatePhoneKeys())->count();

        $page = $query->paginate($filters['per_page'] ?? 20);
        $samePhone = $this->samePhoneCounts($page->getCollection());
        $contacts = $this->contactCounts($page->getCollection()->pluck('id'));

        $page = $page
            ->through(fn (Product $product) => [
                // expires_at modelda yashirin (ilova uchun); admin uchun kerak
                ...$product->makeVisible('expires_at')->toArray(),
                // Xom "Y-m-d H:i:s" iOS Safari'da o'qilmaydi — ISO formatda beramiz
                'expires_at' => $product->expires_at ? Carbon::parse($product->expires_at)->toISOString() : null,
                'is_active' => $this->isActive($product),
                // Shu raqam bilan yana nechta e'lon bor (o'zidan tashqari, arxivsiz)
                'same_phone_count' => max(0, ($samePhone[self::phoneKey($product->phone)] ?? 0) - ($product->trashed() ? 0 : 1)),
                // Sotuvchiga murojaat: "Qo'ng'iroq" va "Telegram" tugmasini necha marta bosishgan (butun davr)
                'phone_clicks' => (int) ($contacts[$product->id]['contact_phone'] ?? 0),
                'telegram_clicks' => (int) ($contacts[$product->id]['contact_telegram'] ?? 0),
            ]);

        return response()->json([...$page->toArray(), 'counts' => $counts]);
    }

    /**
     * E'lonlar bo'yicha bosishlar (activity_stats, ref = e'lon ID). Bitta so'rov — sahifadagi barcha e'lonlar uchun.
     * Bugungi bosishlar cron (activity:aggregate, 10 daqiqada) yig'gach qo'shiladi.
     *
     * @return array<int, array<string, int>>
     */
    private function contactCounts(\Illuminate\Support\Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $result = [];
        DB::table('activity_stats')
            ->whereIn('event', ['contact_phone', 'contact_telegram'])
            ->whereIn('ref', $ids->map(fn ($id) => (string) $id)->all())
            ->groupBy('ref', 'event')
            ->selectRaw('ref, event, SUM(hits) as hits')
            ->get()
            ->each(function ($row) use (&$result) {
                $result[(int) $row->ref][$row->event] = (int) $row->hits;
            });

        return $result;
    }

    /** Telefonning oxirgi 9 raqami (SQL): "+998 93 123-45-67", "93-123-45-67" va "931234567" bir xil kalit */
    // SUBSTR(x, -9): MySQL va SQLite (testlar) ikkalasida ham ishlaydi (RIGHT() SQLite'da yo'q)
    private const PHONE_KEY_SQL = "SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), '-', ''), ' ', ''), '+', ''), '(', ''), ')', ''), -9)";

    private static function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 9 ? substr($digits, -9) : null;
    }

    /** Qidiruv telefon raqamiga o'xshasa (kamida 5 raqam; faqat raqam, +, -, bo'sh joy, qavs) — taqqoslanadigan raqamlar */
    private static function phoneSearchKey(string $search): ?string
    {
        if (! preg_match('/^[\d\s+\-()]+$/', $search)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $search);
        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            $digits = substr($digits, 3);
        }

        return strlen($digits) >= 5 ? $digits : null;
    }

    /** Ikki va undan ortiq (arxivlanmagan) e'londa uchraydigan telefon kalitlari — subquery */
    private function duplicatePhoneKeys()
    {
        return DB::query()->fromSub(
            Product::query()
                ->selectRaw(self::PHONE_KEY_SQL . ' as phone_key')
                ->whereRaw('LENGTH(' . self::PHONE_KEY_SQL . ') = 9')
                ->groupByRaw(self::PHONE_KEY_SQL)
                ->havingRaw('COUNT(*) > 1'),
            'dup',
        )->select('phone_key');
    }

    /** @return array<string, int> telefon kaliti => arxivlanmagan e'lonlar soni */
    private function samePhoneCounts($products): array
    {
        $keys = $products->map(fn (Product $product) => self::phoneKey($product->phone))->filter()->unique()->values();
        if ($keys->isEmpty()) {
            return [];
        }

        return Product::query()
            ->setEagerLoads([])
            ->selectRaw(self::PHONE_KEY_SQL . ' as phone_key, COUNT(*) as total')
            ->whereIn(DB::raw(self::PHONE_KEY_SQL), $keys)
            ->groupByRaw(self::PHONE_KEY_SQL)
            ->pluck('total', 'phone_key')
            ->map(fn ($total) => (int) $total)
            ->all();
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
                        'expires_at' => $product->category ? $product->category->listingExpiresAt() : now()->addDays(Category::DEFAULT_LISTING_DAYS),
                        'published_at' => now(),
                        'sold_at' => null,
                    ])->save();
                    SyncProductToTelegram::dispatch($product->id, SyncProductToTelegram::REPOST);
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
    /**
     * Davr: ?date=YYYY-MM-DD — bitta kun (istalgan), aks holda ?days=1|7|30 — bugun bilan tugaydigan oxirgi kunlar.
     *
     * @return array{0: Carbon, 1: Carbon, 2: int, 3: ?string} [boshi, oxiri, kunlar soni, tanlangan kun]
     */
    private function period(Request $request): array
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', Rule::in([1, 7, 30])],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        if (filled($validated['date'] ?? null)) {
            $day = Carbon::parse($validated['date']);

            return [$day->copy()->startOfDay(), $day->copy()->endOfDay(), 1, $day->toDateString()];
        }
        $days = (int) ($validated['days'] ?? 7);

        return [now()->subDays($days - 1)->startOfDay(), now()->endOfDay(), $days, null];
    }

    public function searchStats(Request $request)
    {
        [$from, $to, $days, $date] = $this->period($request);

        $aggregate = fn ($query) => $query
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('query, COUNT(*) as searches, COUNT(DISTINCT user_id) as users, MAX(created_at) as last_at')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(30)
            ->get();

        return response()->json([
            'days' => $days,
            'date' => $date,
            'total' => DB::table('search_logs')->whereBetween('created_at', [$from, $to])->count(),
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
        [$from, $to, $days, $date] = $this->period($request);

        ActivityLog::aggregate(now()->toDateString());

        $base = fn () => DB::table('activity_stats')->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
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
            'date' => $date,
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
                // "Matn bilan" yo'li: AI'dan qoralama olganlar va undan e'lon joylaganlar
                'ai_drafts' => $users('ai_draft'),
                'ai_created' => $users('ad_created_ai'),
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
            'struggles' => $this->struggles($days, $to->toDateString()),
            'raw_days' => ActivityLog::rawDays(),
        ]);
    }

    /** E'lon qo'ya olmagan / xatoga uchragan foydalanuvchilar (ism bilan), eng yangisi birinchi */
    private function struggles(int $days, ?string $until = null): array
    {
        $rows = collect(ActivityLog::struggles($days, $until));
        $users = User::whereIn('id', $rows->pluck('user_id')->unique())->get(['id', 'name', 'username'])->keyBy('id');

        return $rows
            ->filter(fn (array $row) => $users->has($row['user_id']))
            ->sortByDesc(fn (array $row) => $row['date'] . sprintf('%012d', $row['last_at'] ?? 0))
            ->take(50)
            ->map(fn (array $row) => [
                ...$row,
                'last_at' => $row['last_at'] ? Carbon::createFromTimestamp($row['last_at'])->toISOString() : null,
                'name' => $users[$row['user_id']]->name,
                'username' => $users[$row['user_id']]->username,
            ])
            ->values()
            ->all();
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
                'expires_at' => $product->category ? $product->category->listingExpiresAt() : now()->addDays(Category::DEFAULT_LISTING_DAYS),
                'published_at' => now(),
                'sold_at' => null,
            ])->save();
            // Kanalda ham "hali faol" ekani ko'rinsin: post kanal oxiriga qayta chiqadi
            SyncProductToTelegram::dispatch($product->id, SyncProductToTelegram::REPOST);
        } else {
            $product->update(['expires_at' => now()->subMinute()]);
        }

        return response()->json(['message' => "E'lon holati yangilandi."]);
    }

    /**
     * E'lon muddati: "days" — N kun, "permanent" — muddatsiz (expires_at = null), "category" — kategoriya bo'yicha.
     * Telegram assistent e'lonlari ham shu yerda boshqariladi.
     */
    private const EXPIRY_RULES = [
        'duration' => ['nullable', 'in:category,days,permanent'],
        'days' => ['nullable', 'required_if:duration,days', 'integer', 'min:1', 'max:3650'],
    ];

    /**  array{0: bool, 1: ?Carbon} [muddat berildimi, expires_at] — "category"/bo'sh bo'lsa [false, null] */
    private static function expiryFromInput(array $input): array
    {
        return match ($input['duration'] ?? null) {
            'permanent' => [true, null],
            'days' => [true, now()->addDays((int) $input['days'])],
            default => [false, null],
        };
    }

    public function updateProductExpiry(Request $request, int $id)
    {
        $validated = $request->validate([
            ...self::EXPIRY_RULES,
            'duration' => ['required', 'in:category,days,permanent'],
        ]);

        $product = Product::with('category')->findOrFail($id);
        [$given, $expiresAt] = self::expiryFromInput($validated);
        if (! $given) {
            $expiresAt = $product->category ? $product->category->listingExpiresAt() : now()->addDays(Category::DEFAULT_LISTING_DAYS);
        }
        $product->forceFill(['expires_at' => $expiresAt])->save();

        return response()->json([
            'message' => $expiresAt ? "E'lon " . $expiresAt->format('d.m.Y H:i') . " gacha turadi." : "E'lon endi muddatsiz.",
        ]);
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
            // Kirgan kunlar soni bo'yicha: bir marta kirib qaytmagan / qayta-qayta kirayotgan
            'visits' => ['nullable', Rule::in(['once', 'returning'])],
            'sort' => ['nullable', Rule::in(['new', 'visits', 'active'])],
            // Bugun kirgan / e'lon qo'ygan / faqat shahar tanlagan / hech narsa qilmagan
            'segment' => ['nullable', Rule::in(array_keys(self::userSegments()))],
            // Kanal va bot: ikkalasida / faqat kanalda / faqat botda / hech birida / hali tekshirilmagan
            'subscription' => ['nullable', Rule::in(array_keys(self::subscriptionFilters()))],
            // Shu kuni ro'yxatdan o'tganlar
            'date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $joinedOn = filled($filters['date'] ?? null) ? Carbon::parse($filters['date']) : null;
        $segment = $filters['segment'] ?? null;
        $subscription = $filters['subscription'] ?? null;
        if ($segment === 'today') {
            // Oxirgi daqiqalar ham ko'rinsin (cron 10 daqiqada bir yig'adi)
            ActivityLog::aggregate(now()->toDateString());
        }

        $visitDays = DB::table('user_visits')->whereColumn('user_visits.user_id', 'users.id')->selectRaw('COUNT(*)');
        $lastVisit = DB::table('user_visits')->whereColumn('user_visits.user_id', 'users.id')->selectRaw('MAX(date)');

        $page = User::query()
            ->with([
                'activeDistrict:id,name',
                'channelMemberships' => fn ($query) => $query->select(['id', 'user_id', 'chat_id', 'is_member']),
            ])
            ->when($subscription !== null, fn ($query) => self::subscriptionFilters()[$subscription]($query))
            ->when($joinedOn !== null, fn ($query) => $query->whereBetween('users.created_at', [$joinedOn->copy()->startOfDay(), $joinedOn->copy()->endOfDay()]))
            ->withCount(['products', 'products as active_products_count' => fn ($query) => $query->active()])
            ->addSelect([
                'users.*',
                'last_active_at' => DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereColumn('tokenable_id', 'users.id')
                    ->selectRaw('MAX(last_used_at)'),
                'visit_days' => $visitDays,
                'last_visit_on' => $lastVisit,
            ])
            // 0 kun — ma'lumot yo'q (eski hisob): "bir marta" qatoriga qo'shiladi
            ->when(($filters['visits'] ?? null) === 'once', fn ($query) => $query->where($visitDays, '<=', 1))
            ->when(($filters['visits'] ?? null) === 'returning', fn ($query) => $query->where($visitDays, '>=', 2))
            ->when(($filters['role'] ?? null) === 'blocked', fn ($query) => $query->whereNotNull('blocked_at'))
            ->when(in_array($filters['role'] ?? null, ['user', 'admin'], true), fn ($query) => $query->where('role', $filters['role']))
            ->when($segment !== null, fn ($query) => self::userSegments()[$segment]($query))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = ltrim(trim($filters['search']), '@');
                $phone = self::phoneSearchKey($search);
                $query->where(function ($query) use ($search, $phone): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('telegram_user_id', 'like', "%{$search}%");
                    // Profildagi yoki e'lonlaridagi telefon raqami
                    if ($phone !== null) {
                        $query->orWhereRaw(str_replace('phone', 'users.phone', self::PHONE_KEY_SQL) . ' LIKE ?', ["%{$phone}%"])
                            ->orWhereHas('products', fn ($productQuery) => $productQuery->withTrashed()
                                ->whereRaw(str_replace('phone', 'products.phone', self::PHONE_KEY_SQL) . ' LIKE ?', ["%{$phone}%"]));
                    }
                });
            })
            // "Bugun kirganlar" — oxirgi kirgan birinchi
            ->when($segment === 'today' && ! isset($filters['sort']), fn ($query) => $query->orderByRaw('last_active_at IS NULL')->orderByDesc('last_active_at'))
            ->when(($filters['sort'] ?? 'new') === 'visits', fn ($query) => $query->orderByDesc('visit_days'))
            ->when(($filters['sort'] ?? 'new') === 'active', fn ($query) => $query->orderByRaw('last_active_at IS NULL')->orderByDesc('last_active_at'))
            ->latest('users.id')
            ->paginate($filters['per_page'] ?? 20)
            ->through(fn (User $user) => [
                ...collect($user->toArray())->except('channel_memberships')->all(),
                'last_active_at' => $this->lastActiveAt($user),
                'visit_days' => (int) $user->visit_days,
                ...self::channelFields($user),
            ]);

        return response()->json([
            ...$page->toArray(),
            'channels' => app(ChannelMembershipService::class)->channels(),
            'subscription_counts' => collect(self::subscriptionFilters())
                ->map(fn (\Closure $apply) => $apply(User::query()->whereNotNull('telegram_user_id'))->count()),
            'visit_summary' => $this->visitSummary(),
            'segment_counts' => collect(self::userSegments())
                ->map(fn (\Closure $apply) => $apply(User::query())->count())
                ->put('all', User::count()),
        ]);
    }

    /**
     * Foydalanuvchilar bo'limlari. "posters" / "city_only" / "idle" bir-birini kesmaydi:
     * e'lon qo'ygan (arxivdagisi ham), faqat shahar tanlagan (e'lonsiz), hech narsa qilmagan.
     *
     * @return array<string, \Closure>
     */
    private static function userSegments(): array
    {
        $withArchived = fn ($query) => $query->withTrashed();

        return [
            'today' => fn ($query) => $query->where(fn ($query) => $query
                ->whereExists(fn ($sub) => $sub->from('user_visits')
                    ->whereColumn('user_visits.user_id', 'users.id')
                    ->where('user_visits.date', now()->toDateString()))
                ->orWhereExists(fn ($sub) => $sub->from('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereColumn('tokenable_id', 'users.id')
                    ->where('last_used_at', '>=', now()->startOfDay()))),
            'posters' => fn ($query) => $query->whereHas('products', $withArchived),
            'city_only' => fn ($query) => $query->whereNotNull('active_district_id')->whereDoesntHave('products', $withArchived),
            'idle' => fn ($query) => $query->whereNull('active_district_id')->whereDoesntHave('products', $withArchived),
            // Bot xabar yubora oladi (botga /start bosgan yoki mini app'da yozishga ruxsat bergan)
            'bot' => fn ($query) => $query->where('bot_can_message', true),
        ];
    }

    /**
     * Kanal + bot obunasi bo'yicha bo'limlar (bir-birini kesmaydi). "Kanalda" — sozlangan kanallardan
     * kamida bittasiga a'zo. "Tekshirilmagan" — kanal a'zoligi hali so'ralmagan (users:check-channels).
     *
     * @return array<string, \Closure>
     */
    private static function subscriptionFilters(): array
    {
        $inChannel = fn ($query) => $query->whereHas('channelMemberships', fn ($sub) => $sub->where('is_member', true));
        $notInChannel = fn ($query) => $query->whereNotNull('channels_checked_at')
            ->whereDoesntHave('channelMemberships', fn ($sub) => $sub->where('is_member', true));

        return [
            'both' => fn ($query) => $inChannel($query)->where('bot_can_message', true),
            'channel_only' => fn ($query) => $inChannel($query)->where('bot_can_message', false),
            'bot_only' => fn ($query) => $notInChannel($query)->where('bot_can_message', true),
            'none' => fn ($query) => $notInChannel($query)->where('bot_can_message', false),
            'unchecked' => fn ($query) => $query->whereNull('channels_checked_at')
                ->whereDoesntHave('channelMemberships', fn ($sub) => $sub->where('is_member', true)),
        ];
    }

    /** Ro'yxat qatori uchun: qaysi kanallarga a'zo va qachon tekshirilgan */
    private static function channelFields(User $user): array
    {
        return [
            'channel_ids' => $user->channelMemberships->where('is_member', true)->pluck('chat_id')->values()->all(),
            'channels_checked_at' => $user->channels_checked_at?->toIso8601String(),
        ];
    }

    /** Bitta foydalanuvchining kanal a'zoligini hozir Telegram'dan qayta so'rash */
    public function checkUserChannels(int $id, ChannelMembershipService $service)
    {
        $user = User::findOrFail($id);
        abort_unless($user->telegram_user_id, 422, "Bu foydalanuvchida Telegram ID yo'q.");

        $ok = $service->check($user);
        $user->load('channelMemberships');

        return response()->json([
            ...self::channelFields($user),
            'message' => $ok ? 'Kanal a\'zoligi yangilandi.' : "Telegram javob bermadi — keyinroq qayta urinib ko'ring.",
        ], $ok ? 200 : 502);
    }

    /**
     * Foydalanuvchining ilovadagi yo'li (bir kun): qaysi sahifalarga kirdi, nimani ko'rdi, qayerda xato oldi.
     * Xom yozuvlar oxirgi ActivityLog::rawDays() kun saqlanadi.
     */
    public function userJourney(Request $request, int $id)
    {
        $user = User::with('activeDistrict:id,name')->findOrFail($id);
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? null;

        ActivityLog::aggregate(now()->toDateString());
        $since = now()->subDays(ActivityLog::rawDays() - 1)->toDateString();
        $dates = DB::table('user_visits')->where('user_id', $id)->where('date', '>=', $since)
            ->orderByDesc('date')->pluck('date')->map(fn ($value) => substr((string) $value, 0, 10))->values();
        $date ??= $dates->first() ?? now()->toDateString();

        $events = ActivityLog::userEvents($id, $date);

        // Nomlar: e'lon sarlavhasi, kategoriya, hudud
        $refs = fn (array $names) => collect($events)->whereIn('e', $names)->pluck('r')
            ->filter(fn ($ref) => ctype_digit((string) $ref))->unique()->values();
        $productEvents = ['product_view', 'ad_edited', 'ad_activated', 'ad_deactivated', 'report', 'contact_phone', 'contact_telegram'];
        $categoryEvents = ['category_open', 'ad_created', 'ad_created_ai'];
        $products = Product::withTrashed()->setEagerLoads([])->whereIn('id', $refs($productEvents))->pluck('title', 'id');
        $categories = Category::withTrashed()->whereIn('id', $refs($categoryEvents))->pluck('name', 'id');
        $districts = \App\Models\District::whereIn('id', $refs(['district_change']))->pluck('name', 'id');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'district' => $user->activeDistrict?->name,
            ],
            'date' => $date,
            'dates' => $dates,
            'raw_days' => ActivityLog::rawDays(),
            'events' => array_map(fn (array $event) => [
                ...$event,
                't' => $event['t'] ? Carbon::createFromTimestamp($event['t'])->toISOString() : null,
                'label' => match (true) {
                    in_array($event['e'], $productEvents, true) => $products[$event['r']] ?? null,
                    in_array($event['e'], $categoryEvents, true) => $categories[$event['r']] ?? null,
                    $event['e'] === 'district_change' => $event['r'] === 'all' ? 'Barcha shaharlar' : ($districts[$event['r']] ?? null),
                    default => null,
                },
            ], $events),
        ]);
    }

    /**
     * Qaytib kirish ko'rsatkichi (kunlar bo'yicha, bir kunda necha marta ochgani ahamiyatsiz):
     * bir marta kirib qaytmaganlar, 2+ kun kirganlar, ulardan 5+ kun kirganlar va oxirgi 7 kunda qaytganlar.
     */
    private function visitSummary(): array
    {
        $perUser = DB::table('user_visits')
            ->selectRaw('user_id, COUNT(*) as visit_count, MIN(date) as first_date, MAX(date) as last_date')
            ->groupBy('user_id');

        $row = DB::query()->fromSub($perUser, 'v')
            ->selectRaw('SUM(CASE WHEN visit_count >= 2 THEN 1 ELSE 0 END) as returning_users')
            ->selectRaw('SUM(CASE WHEN visit_count >= 5 THEN 1 ELSE 0 END) as regular_users')
            // Oxirgi 7 kunda kirgan va undan oldin ham kirgan (shu hafta yangi emas)
            ->selectRaw('SUM(CASE WHEN last_date >= ? AND first_date < ? THEN 1 ELSE 0 END) as returned_week', [
                now()->subDays(6)->toDateString(),
                now()->subDays(6)->toDateString(),
            ])
            ->first();

        $total = User::count();
        $returning = (int) ($row->returning_users ?? 0);

        return [
            'total' => $total,
            'once' => $total - $returning,
            'returning' => $returning,
            'regular' => (int) ($row->regular_users ?? 0),
            'returned_week' => (int) ($row->returned_week ?? 0),
        ];
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

    /**
     * E'lon muallifiga (product_id) yoki foydalanuvchining o'ziga (user_id) ogohlantirish/tushuntirish yuborish.
     * Bot orqali yuboriladi va ilovada Profil bo'limida turadi (o'qilmagani menyuda belgi bo'lib ko'rinadi).
     */
    public function sendUserMessage(Request $request, \App\Services\AdminMessageService $service)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'required_without:user_id'],
            'user_id' => ['nullable', 'integer', 'required_without:product_id'],
            'type' => ['required', Rule::in(\App\Models\AdminMessage::TYPES)],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $product = filled($validated['product_id'] ?? null)
            ? Product::withTrashed()->with('user')->findOrFail($validated['product_id'])
            : null;
        $recipient = $product?->user ?? User::findOrFail($validated['user_id']);

        abort_if(
            $recipient->id === User::getBotUser()->id,
            422,
            "Bu e'lonni Telegram assistent qo'ygan — asl muallifi ilovada yo'q.",
        );

        $message = $service->send($recipient, $request->user(), $validated['type'], $validated['body'], $product);

        return response()->json([
            'message' => $message->telegram_sent
                ? "Xabar yuborildi: botga yetkazildi va ilovada Profil bo'limida ham turadi."
                : "Xabar saqlandi: botga yetkazib bo'lmadi (botni ishga tushirmagan) — ilovada Profil bo'limida ko'radi.",
            'item' => $message,
        ]);
    }

    /** Foydalanuvchiga avval yuborilgan xabarlar (oxirgi 20 ta) — yozish oynasida tarix sifatida */
    public function userMessages(int $id)
    {
        return \App\Models\AdminMessage::where('user_id', $id)
            ->with(['product:id,title', 'sender:id,name'])
            ->latest('id')
            ->take(20)
            ->get();
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
                // ISO: vaqt ilovada lotincha formatlanadi (Carbon'ning "uz" tili kirillcha)
                'last_run_at' => $setting->last_run_at?->toISOString(),
                'ai_usage' => \App\Services\Bot\AdAiParserService::todayUsage(),
                'ai_model' => match ($setting->ai_provider) {
                    'gemini' => config('services.gemini.model'),
                    'openai' => config('services.openai.model'),
                    default => null,
                },
                'ai_models' => [
                    'gemini' => config('services.gemini.model'),
                    'openai' => config('services.openai.model'),
                ],
                'ai_keys' => [
                    'gemini' => filled(config('services.gemini.api_key')),
                    'openai' => filled(config('services.openai.api_key')),
                ],
                // Bugun kanallardan ko'rilgan postlar nima bo'ldi: qo'shildi / filtrlandi / dublikat / AI rad etdi / eski
                'today_seen' => \App\Models\BotSeenPost::where('created_at', '>=', now()->startOfDay())
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
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

    /**
     * Admin tanlagan post havolasi (t.me/kanal/123) — filtrsiz, albatta e'lon bo'lib joylanadi.
     */
    public function importBotPost(Request $request, \App\Services\Bot\BotAdImportService $service)
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:300'],
            'publish' => ['boolean'],
        ]);

        return $this->chosenImportResponse(
            $service->importChosen($validated['url'], (bool) ($validated['publish'] ?? false))
        );
    }

    /**
     * Admin guruhdan nusxalagan e'lon matni va o'zi qo'ygan rasmlar — importBotPost kabi filtrsiz joylanadi.
     */
    public function importBotText(Request $request, \App\Services\Bot\BotAdImportService $service)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4096'],
            'images' => ['array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:10240'],
            'publish' => ['boolean'],
            ...self::EXPIRY_RULES,
        ]);

        return $this->chosenImportResponse(
            $service->importText(
                $validated['text'],
                $request->file('images', []),
                $request->boolean('publish'),
                self::expiryFromInput($validated),
            )
        );
    }

    private function chosenImportResponse(array $result)
    {
        $product = $result['product'] ?? null;

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            'parsed_by' => $result['parsed_by'] ?? null,
            'product' => $product ? [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'category' => $product->category?->name,
                'district' => $product->district?->name,
                'images' => $product->images->map(fn ($image) => '/storage/' . ($image->thumb_src ?: $image->src))->values(),
            ] : null,
        ], in_array($result['status'], ['imported', 'exists'], true) ? 200 : 422);
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
            // Bot xabar yubora oladigan (bloklanmagan) foydalanuvchilar — "Bot obunachilari" tanlovida
            'bot_audience' => \App\Services\BroadcastService::audience()->count(),
        ]);
    }

    /** Kanaldagi qadalgan "jonli" post: holati va oldindan ko'rish */
    public function channelLivePost(\App\Services\ChannelLivePostService $service)
    {
        $content = $service->content();

        return response()->json([
            'enabled' => \App\Services\ChannelLivePostService::enabled(),
            'posted' => \App\Services\ChannelLivePostService::isPosted(),
            'channels' => \App\Services\TelegramProductService::channels(),
            'preview' => $content['body'],
            'buttons' => array_map(fn (array $button) => $button[0], $content['buttons']),
        ]);
    }

    /**
     * Yoqish: post darhol yoziladi/yangilanadi va qadaladi. O'chirish: post kanaldan olib tashlanadi.
     * refresh=1 — yoqilgan holatda hozir yangilash.
     */
    public function updateChannelLivePost(Request $request, \App\Services\ChannelLivePostService $service)
    {
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);

        if (empty(\App\Services\TelegramProductService::channels())) {
            return response()->json(['message' => "Kanal sozlanmagan: .env faylida TELEGRAM_CHANNELS bo'sh."], 422);
        }

        \App\Services\ChannelLivePostService::setEnabled($validated['enabled']);

        if (! $validated['enabled']) {
            $service->remove();
            return response()->json(['message' => "Jonli post o'chirildi va kanaldan olib tashlandi."]);
        }

        $results = $service->sync(force: true);
        if (in_array('failed', $results, true)) {
            return response()->json([
                'message' => "Kanalga yozib bo'lmadi. Bot kanalda admin ekanini (xabar yozish, tahrirlash va qadash huquqi) tekshiring.",
            ], 422);
        }

        return response()->json(['message' => "Jonli post kanalda turibdi va har 15 daqiqada yangilanadi."]);
    }

    /**
     * Post: kanal(lar)ga va/yoki bot obunachilariga (bot xabar yubora oladiganlarga).
     * Matn Telegram uslubidagi belgilar bilan (**qalin**, __qiya__, ||spoiler||, [havola](url), "> " iqtibos) —
     * TelegramMarkdown uni Telegram HTML'ga aylantiradi. test=1 — faqat adminning o'ziga sinov uchun.
     */
    public function sendChannelPost(Request $request, \App\Services\ChannelPostService $service, \App\Services\BroadcastService $broadcasts)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:8000'],
            // Sinovda (faqat o'ziga) qayerga tanlash shart emas
            'targets' => [Rule::requiredIf(fn () => ! $request->boolean('test')), 'array'],
            'targets.*' => [Rule::in(['channel', 'bot'])],
            'test' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'button_text' => ['nullable', 'required_with:button_url', 'string', 'max:64'],
            'button_url' => ['nullable', 'required_with:button_text', 'string', 'max:512', 'regex:/^(https?:\/\/|tg:\/\/)\S+$/i'],
        ], [
            'button_url.regex' => "Havola https://, http:// yoki tg:// bilan boshlanishi kerak.",
            'targets.required' => "Qayerga yuborishni tanlang: kanal yoki bot obunachilari.",
        ]);

        $html = \App\Support\TelegramMarkdown::toHtml($validated['text']);
        // Telegram cheklovi ko'rinadigan matnga: rasm izohi 1024, oddiy xabar 4096 belgi
        $limit = $request->hasFile('photo') ? 1024 : 4096;
        if (\App\Support\TelegramMarkdown::visibleLength($html) > $limit) {
            return response()->json([
                'message' => "Matn juda uzun: Telegram {$limit} belgidan ko'pini qabul qilmaydi.",
                'errors' => ['text' => ["Matn {$limit} belgidan oshmasin."]],
            ], 422);
        }

        $buttonText = $validated['button_text'] ?? null;
        $buttonUrl = $validated['button_url'] ?? null;
        $targets = $validated['targets'] ?? [];
        $photoPath = $request->hasFile('photo') ? $service->preparePhoto($request->file('photo')) : null;

        try {
            if ($request->boolean('test')) {
                $ok = $broadcasts->sendTest($request->user(), $html, $photoPath, $buttonText, $buttonUrl);

                return $ok
                    ? response()->json(['message' => "Sinov posti sizga botda yuborildi — Telegram'da tekshiring."])
                    : response()->json(['message' => "Sizga yuborib bo'lmadi: botni ishga tushirganingizni (/start) tekshiring yoki matndagi formatlashni ko'ring."], 422);
            }

            $messages = [];
            $sent = [];
            if (in_array('channel', $targets, true)) {
                try {
                    $sent = $service->send($html, $photoPath, $buttonText, $buttonUrl);
                } catch (\Throwable $e) {
                    return response()->json(['message' => "Telegram postni qabul qilmadi: {$e->getMessage()}"], 422);
                }
                $messages[] = count($sent) > 1 ? count($sent) . ' ta kanalga yuborildi' : 'Kanalga yuborildi';
            }

            if (in_array('bot', $targets, true)) {
                // Rasm butun yuborish davomida kerak — vaqtinchalik papkadan alohida joyga (tugagach o'chiriladi)
                $keptPhoto = null;
                if ($photoPath) {
                    $keptPhoto = storage_path('app/broadcasts/' . basename($photoPath));
                    \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($keptPhoto));
                    \Illuminate\Support\Facades\File::copy($photoPath, $keptPhoto);
                }
                $broadcast = $broadcasts->start($request->user(), $validated['text'], $html, $keptPhoto, $buttonText, $buttonUrl);
                $messages[] = $broadcast->total
                    ? "bot orqali {$broadcast->total} kishiga yuborilmoqda (bir necha daqiqa)"
                    : "bot obunachilari hozircha yo'q";
            }

            return response()->json(['message' => ucfirst(implode('; ', $messages)) . '.', 'sent' => $sent]);
        } finally {
            if ($photoPath) {
                \Illuminate\Support\Facades\File::delete($photoPath);
            }
        }
    }

    /** Oldindan ko'rish: yuborishda ishlatiladigan o'sha HTML (ko'rinish va natija bir xil bo'lsin) */
    public function postPreview(Request $request)
    {
        $text = (string) $request->validate(['text' => ['nullable', 'string', 'max:8000']])['text'];
        $html = \App\Support\TelegramMarkdown::toHtml($text);

        return response()->json(['html' => $html, 'length' => \App\Support\TelegramMarkdown::visibleLength($html)]);
    }

    /** Bot obunachilariga yuborilgan oxirgi postlar va ularning hisobi */
    public function broadcasts()
    {
        return \App\Models\Broadcast::with('author:id,name')->latest('id')->take(10)->get()
            ->map(fn (\App\Models\Broadcast $broadcast) => [
                'id' => $broadcast->id,
                'text' => \Illuminate\Support\Str::limit($broadcast->text, 140),
                'total' => $broadcast->total,
                'sent' => $broadcast->sent,
                'failed' => $broadcast->failed,
                'author' => $broadcast->author?->name,
                'created_at' => $broadcast->created_at?->toISOString(),
                'finished_at' => $broadcast->finished_at?->toISOString(),
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
            && ($product->isPermanent() || Carbon::parse($product->expires_at)->isFuture());
    }
}
