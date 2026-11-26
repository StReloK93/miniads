<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin bosh sahifasi: tanlangan BIR KUN bo'yicha hisobot (bugun, kecha yoki istalgan sana).
 * Har ko'rsatkich oldingi kun bilan solishtiriladi; 14 kunlik grafikdan kunni bosib tanlash mumkin.
 */
class AdminDayController extends Controller
{
    private const TREND_DAYS = 14;

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $day = Carbon::parse($validated['date'] ?? now()->toDateString())->startOfDay();
        $previous = $day->copy()->subDay();
        $date = $day->toDateString();

        // Bugungi faollik so'nggi daqiqalari bilan ko'rinsin (cron 10 daqiqada yig'adi)
        if ($day->isToday()) {
            ActivityLog::aggregate($date);
        }

        return response()->json([
            'date' => $date,
            'is_today' => $day->isToday(),
            'metrics' => $this->metrics($day),
            'previous' => $this->metrics($previous),
            'trend' => $this->trend($day),
            'hours' => $this->hours($day),
            'products' => $this->dayProducts($day),
            'users' => $this->dayUsers($day),
            'categories' => $this->dayCategories($day),
            'searches' => $this->daySearches($day),
            'attention' => [
                // Hozirgi holat (kunga bog'liq emas): ko'rilmagan shikoyatlar
                'open_reports' => Product::whereHas('openReports')->count(),
                'struggles' => count(ActivityLog::struggles(1, $date)),
            ],
            'totals' => [
                'active_products' => Product::active()->count(),
                'users' => User::count(),
                'bot_reachable' => User::where('bot_can_message', true)->whereNull('blocked_at')->count(),
            ],
        ]);
    }

    /** @return array<string, int> */
    private function metrics(Carbon $day): array
    {
        [$from, $to] = [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
        $products = Product::withTrashed()->whereBetween('created_at', [$from, $to]);
        $ads = (clone $products)->count();
        $ai = (clone $products)->where('is_bot', true)->count();
        $activity = DB::table('activity_stats')->where('date', $day->toDateString())
            ->selectRaw('event, SUM(hits) as hits, SUM(users) as users')
            ->groupBy('event')
            ->get()
            ->keyBy('event');
        $hits = fn (string $event) => (int) ($activity[$event]->hits ?? 0);
        $searches = DB::table('search_logs')->whereBetween('created_at', [$from, $to]);

        return [
            'ads' => $ads,
            'ads_people' => $ads - $ai,
            'ads_ai' => $ai,
            'new_users' => User::whereBetween('created_at', [$from, $to])->count(),
            'visitors' => (int) ($activity['visitors']->users ?? 0),
            'product_views' => $hits('product_view'),
            'contacts' => $hits('contact_phone') + $hits('contact_telegram'),
            'favorites' => $hits('favorite_add'),
            'searches' => (clone $searches)->count(),
            'searches_empty' => (clone $searches)->where('results_count', 0)->count(),
            'sold' => Product::withTrashed()->whereBetween('sold_at', [$from, $to])->count(),
            'reports' => DB::table('product_reports')->whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    /** Tanlangan kun bilan tugaydigan 14 kun: e'lonlar (odam / AI), yangi odamlar, kirganlar */
    private function trend(Carbon $day): array
    {
        $start = $day->copy()->subDays(self::TREND_DAYS - 1)->startOfDay();
        $end = $day->copy()->endOfDay();

        $ads = DB::table('products')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total, SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as ai')
            ->groupBy('d')
            ->get()
            ->keyBy('d');
        $users = DB::table('users')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');
        // Faollik: kirganlar (kishi), e'lon ko'rishlar va sotuvchiga murojaatlar (bosishlar)
        $activity = [];
        DB::table('activity_stats')
            ->whereIn('event', ['visitors', 'product_view', 'contact_phone', 'contact_telegram'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, event, SUM(users) as users, SUM(hits) as hits')
            ->groupBy('date', 'event')
            ->get()
            ->each(function ($row) use (&$activity) {
                $day = substr((string) $row->date, 0, 10);
                $activity[$day][$row->event] = $row->event === 'visitors' ? (int) $row->users : (int) $row->hits;
            });
        $searches = DB::table('search_logs')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $rows = [];
        for ($i = 0; $i < self::TREND_DAYS; $i++) {
            $key = $start->copy()->addDays($i)->toDateString();
            $total = (int) ($ads[$key]->total ?? 0);
            $rows[] = [
                'date' => $key,
                'ads' => $total,
                'ads_ai' => (int) ($ads[$key]->ai ?? 0),
                'new_users' => (int) ($users[$key] ?? 0),
                'visitors' => $activity[$key]['visitors'] ?? 0,
                'product_views' => $activity[$key]['product_view'] ?? 0,
                'contacts' => ($activity[$key]['contact_phone'] ?? 0) + ($activity[$key]['contact_telegram'] ?? 0),
                'searches' => (int) ($searches[$key] ?? 0),
            ];
        }

        return $rows;
    }

    /** Kun ichida soatlar bo'yicha yangi e'lonlar (24 ta qiymat) */
    private function hours(Carbon $day): array
    {
        $hours = array_fill(0, 24, 0);
        Product::withTrashed()
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->pluck('created_at')
            ->each(function ($createdAt) use (&$hours) {
                $hours[(int) Carbon::parse($createdAt)->format('G')]++;
            });

        return $hours;
    }

    private function dayProducts(Carbon $day): array
    {
        return Product::withTrashed()
            ->with(['user:id,name,username', 'category:id,name', 'district:id,name'])
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'is_bot' => (bool) $product->is_bot,
                'deleted' => $product->deleted_at !== null,
                'created_at' => $product->created_at?->toISOString(),
                'category' => $product->category?->name,
                'district' => $product->district?->name,
                'user' => $product->user?->name,
                'image' => self::imageUrl($product->images->first()?->thumb_src ?: $product->images->first()?->src),
            ])
            ->all();
    }

    private static function imageUrl(?string $src): ?string
    {
        if (! $src) {
            return null;
        }

        return str_starts_with($src, 'http') ? $src : '/storage/' . ltrim($src, '/');
    }

    private function dayUsers(Carbon $day): array
    {
        return User::query()
            ->with('activeDistrict:id,name')
            ->withCount('products')
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'district' => $user->activeDistrict?->name,
                'products_count' => $user->products_count,
                'bot' => (bool) $user->bot_can_message,
                'created_at' => $user->created_at?->toISOString(),
            ])
            ->all();
    }

    private function dayCategories(Carbon $day): array
    {
        return DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('products.created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->selectRaw('categories.id, categories.name, COUNT(products.id) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'count' => (int) $row->count])
            ->all();
    }

    private function daySearches(Carbon $day): array
    {
        return DB::table('search_logs')
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->selectRaw('query, COUNT(*) as count, MIN(results_count) as min_results')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['query' => $row->query, 'count' => (int) $row->count, 'empty' => (int) $row->min_results === 0])
            ->all();
    }
}
