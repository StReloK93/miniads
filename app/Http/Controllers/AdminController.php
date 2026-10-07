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
        return response()->json([
            'stats' => [
                'products' => Product::count(),
                'active_products' => Product::active()->count(),
                'expired_products' => Product::passive()->count(),
                'deleted_products' => Product::onlyTrashed()->count(),
                'users' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'categories' => Category::count(),
            ],
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
        abort_if($product->trashed(), 422, 'O‘chirilgan e’lonni avval tiklang.');

        if ($validated['status'] === 'active') {
            $product->loadMissing('category');
            $product->update([
                'expires_at' => now()->addDays($product->category?->listing_duration_days ?: 30),
                'published_at' => now(),
            ]);
        } else {
            $product->update(['expires_at' => now()->subMinute()]);
        }

        return response()->json(['message' => 'E’lon holati yangilandi.']);
    }

    public function deleteProduct(int $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'E’lon arxivga ko‘chirildi.']);
    }

    public function restoreProduct(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return response()->json(['message' => 'E’lon tiklandi.']);
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
            ->through(fn (User $user) => $user->toArray());
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
                    'O‘zingizning admin huquqingizni olib tashlay olmaysiz.',
                );

                $adminCount = User::query()->where('role', 'admin')->lockForUpdate()->count();
                abort_if($adminCount <= 1, 422, 'Oxirgi admin huquqini olib tashlab bo‘lmaydi.');
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
