<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            ->selectRaw('COALESCE(districts.name, "Belgilanmagan") as name, COUNT(products.id) as count')
            ->groupBy('districts.name')
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

        $dailyAds = [];
        $dayNames = [
            1 => 'Du', 2 => 'Se', 3 => 'Chor', 4 => 'Pay', 5 => 'Juma', 6 => 'Shan', 0 => 'Yak',
        ];

        for ($i = 13; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateKey = $dateObj->toDateString();
            $dailyAds[] = [
                'date' => $dateKey,
                'day_label' => $dateObj->format('d-M'),
                'day_name' => $dayNames[(int) $dateObj->format('w')],
                'count' => (int) ($rawDaily[$dateKey] ?? 0),
            ];
        }

        // Haftaning qaysi kunlarida ko'proq e'lon qo'yilishi (DB-agnostic)
        $dowMapping = [
            1 => 'Dushanba',
            2 => 'Seshanba',
            3 => 'Chorshanba',
            4 => 'Payshanba',
            5 => 'Juma',
            6 => 'Shanba',
            0 => 'Yakshanba',
        ];

        $dowCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 0 => 0];
        DB::table('products')
            ->whereNull('deleted_at')
            ->select('created_at')
            ->get()
            ->each(function ($row) use (&$dowCounts): void {
                if ($row->created_at) {
                    $w = (int) Carbon::parse($row->created_at)->format('w');
                    $dowCounts[$w] = ($dowCounts[$w] ?? 0) + 1;
                }
            });

        $weeklyDistribution = [];
        $maxDowCount = -1;
        $busiestDay = '—';
        foreach ($dowMapping as $w => $dayName) {
            $cnt = $dowCounts[$w] ?? 0;
            $weeklyDistribution[] = [
                'day' => $dayName,
                'count' => $cnt,
            ];
            if ($cnt > $maxDowCount && $cnt > 0) {
                $maxDowCount = $cnt;
                $busiestDay = $dayName;
            }
        }

        // Foydalanuvchilar faolligi ko'rsatkichlari
        $activeUsersToday = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('last_used_at', '>=', now()->subHours(24))
            ->distinct('tokenable_id')
            ->count('tokenable_id');

        $activeUsersWeek = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('last_used_at', '>=', now()->subDays(7))
            ->distinct('tokenable_id')
            ->count('tokenable_id');

        $newUsersWeek = User::where('created_at', '>=', now()->subDays(7))->count();

        // So'nggi faol / yangi foydalanuvchilar
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
            ->orderByRaw('COALESCE(last_active_at, updated_at, created_at) DESC')
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
                'last_active_at' => $user->last_active_at ?: ($user->updated_at?->toISOString() ?: $user->created_at?->toISOString()),
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
            'status' => ['nullable', Rule::in(['active', 'expired', 'deleted', 'all'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Product::query()
            ->with(['user:id,name,username', 'category:id,name', 'district:id,name', 'images:id,product_id,src'])
            ->withTrashed()
            ->when(($filters['status'] ?? 'all') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when(($filters['status'] ?? 'all') === 'active', fn ($query) => $query->whereNull('deleted_at')->active())
            ->when(($filters['status'] ?? 'all') === 'expired', fn ($query) => $query->whereNull('deleted_at')->passive())
            ->when(($filters['status'] ?? 'all') !== 'deleted', fn ($query) => $query->orderByRaw('deleted_at IS NULL DESC'))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        return $query->paginate($filters['per_page'] ?? 20)
            ->through(fn (Product $product) => [
                ...$product->toArray(),
            'is_active' => $this->isActive($product),
            ]);
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
            $product->update([
                'expires_at' => now()->addDays($product->category?->listing_duration_days ?: 30),
                'published_at' => now(),
            ]);
        } else {
            $product->update(['expires_at' => now()->subMinute()]);
        }

        return response()->json(['message' => "E'lon holati yangilandi."]);
    }

    public function deleteProduct(int $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

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
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return User::query()
            ->with('activeDistrict:id,name')
            ->withCount('products')
            ->addSelect([
                'users.*',
                'last_active_at' => DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereColumn('tokenable_id', 'users.id')
                    ->selectRaw('MAX(last_used_at)'),
            ])
            ->when(isset($filters['role']), fn ($query) => $query->where('role', $filters['role']))
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
                'last_active_at' => $user->last_active_at ?: ($user->updated_at?->toISOString() ?: $user->created_at?->toISOString()),
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

    private function isActive(Product $product): bool
    {
        return ! $product->trashed()
            && filled($product->expires_at)
            && Carbon::parse($product->expires_at)->isFuture();
    }
}
