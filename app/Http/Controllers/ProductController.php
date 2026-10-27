<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductReport;
use App\Services\ModerationService;
use Illuminate\Validation\Rule;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductViewService;
use App\Services\TelegramProductService;
use App\Services\ProductSearchService;
use App\Http\Requests\SaveProductRequest;
class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected TelegramProductService $telegramProductService
    ) {
    }

    public function index()
    {
        return Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->active()
            ->get();
    }

    public function store(SaveProductRequest $request)
    {
        $this->abortIfBlocked($request);
        $product = $this->productService->store($request);

        // Birinchi e'londagi raqam profilga yoziladi — keyingi e'lonlarda forma o'zi to'ldiradi
        $user = $request->user();
        if (! $user->phone && filled($request->input('phone'))) {
            $user->forceFill(['phone' => $request->input('phone')])->save();
        }
        $this->scanAfterResponse($product);

        try {
            $this->telegramProductService->publish($product);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Telegram nashr qilish xatosi: " . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e,
            ]);
        }

        return response()->json([
            'message' => __('messages.ad_created'),
            'id' => $product->id,
        ], 201);
    }

    public function update(SaveProductRequest $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        // Admin ham tahrirlay oladi (admin paneldagi "Tahrirlash" — ilovaning tahrirlash sahifasi)
        $isAdmin = $request->user()->role === 'admin';
        if (!$isAdmin && !$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }
        if (!$isAdmin) {
            $this->abortIfBlocked($request);
        }

        $previouslyHadImage = $product->images->isNotEmpty();
        $this->productService->update($request, $product);
        if (!$isAdmin) {
            $this->scanAfterResponse($product);
        }

        try {
            $this->telegramProductService->synchronize($product, $previouslyHadImage);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Telegram sinxronizatsiya xatosi: " . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e,
            ]);
        }

        return response()->json([
            'message' => __('messages.ad_updated'),
        ]);
    }

    public function show($id, ProductViewService $viewService)
    {
        $product = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->with(['user', 'category.parent.parent'])
            ->find($id);

        if (!$product) {
            return response()->json([
                'message' => __('messages.ad_not_found'),
            ], 404);
        }

        $user = auth()->user();
        $isOwnerOrAdmin = $user && ($user->id === $product->user_id || $user->role === 'admin');
        if ($product->expires_at && $product->expires_at < now() && !$isOwnerOrAdmin) {
            return response()->json([
                'message' => __('messages.ad_not_found'),
                'is_expired' => true,
            ], 404);
        }

        if (auth()->id() !== $product->user_id) {
            $viewService->record($product, auth()->id());
        }

        // Shunga yaqin (o'xshash) faol e'lonlarni yuklash
        $categoryIds = [$product->category_id];
        if ($product->category && $product->category->parent_id) {
            $siblingIds = Category::where('parent_id', $product->category->parent_id)->pluck('id')->toArray();
            $categoryIds = array_unique(array_merge($categoryIds, $siblingIds));
        }

        $similarProducts = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->active()
            ->where('id', '!=', $product->id)
            ->whereIn('category_id', $categoryIds)
            ->latest('published_at')
            ->latest('id')
            ->take(3)
            ->get();

        $product->setRelation('similar_products', $similarProducts);

        // Sotuvchining boshqa e'lonlari — ishonch uchun. Bot e'lonlari turli odamlarniki, ularda ma'nosiz
        $sellerProducts = $product->is_bot ? collect() : Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->active()
            ->where('user_id', $product->user_id)
            ->where('id', '!=', $product->id)
            ->latest('published_at')
            ->latest('id')
            ->take(6)
            ->get();

        $product->setRelation('seller_products', $sellerProducts);

        return response()->json($product);
    }


    public function edit(Request $request, int $id)
    {
        $product = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->with(['user'])
            ->findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id) && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $product;
    }

    public function feed(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 12), 4), 50);

        return Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->when(auth()->user()?->active_district_id, function ($q) {
                $q->where(function ($query) {
                    $query->where('district_id', auth()->user()->active_district_id)
                        ->orWhereNull('district_id');
                });
            })
            ->active()
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function latestTen(Request $request)
    {
        // Agar requestda paginate/page parametri bo'lsa yoki frontend scroll qilsa, feed chaqiriladi
        if ($request->has('page')) {
            return $this->feed($request);
        }

        return Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->when(auth()->user()?->active_district_id, function ($q) {
                $q->where(function ($query) {
                    $query->where('district_id', auth()->user()->active_district_id)
                        ->orWhereNull('district_id');
                });
            })
            ->active()
            ->latest('published_at')
            ->take(25)
            ->get();
    }

    public function myAds(Request $request, $status)
    {
        $query = Product::where('user_id', $request->user()->id)
            ->latest();

        match ($status) {
            'active' => $query->active(),
            'expired' => $query->passive(),
            default => null,
        };

        return $query->get()->each
            ->append('days');
    }

    public function search(Request $request, ProductSearchService $searchService)
    {
        return $searchService->search($request);
    }


    public function activate(Request $request, int $id)
    {
        $product = Product::with('category')->findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $this->abortIfBlocked($request);

        $this->productService->activate($product);

        return response()->json([
            'message' => __('messages.ad_activated'),
        ]);
    }

    public function deActivate(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->productService->deActivate($product);

        return response()->json([
            'message' => __('messages.ad_deactivated'),
        ]);
    }





    public function destroy(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->productService->delete($product);

        try {
            $this->telegramProductService->deleteFromChannels($product);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Telegram postni o'chirish xatosi: " . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e,
            ]);
        }

        return response()->json([
            'message' => __('messages.ad_deleted'),
        ]);
    }

    /**
     * E'lon ustidan shikoyat. Bitta foydalanuvchi — bitta shikoyat: qayta yuborsa sababi yangilanadi
     * va (admin avval yopgan bo'lsa ham) yana ko'rib chiqishga tushadi.
     */
    public function report(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(ProductReport::REASONS)],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($id);
        abort_if((int) $product->user_id === (int) $request->user()->id, 422, __('messages.report_own'));

        ProductReport::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $request->user()->id],
            [...$validated, 'resolved_at' => null],
        );

        // Admin chatiga xabar javobdan keyin: foydalanuvchi Telegram API'ni kutib qolmasin
        app()->terminating(fn () => app(ModerationService::class)->reportAdded($product));

        return response()->json(['message' => __('messages.report_sent')]);
    }

    private function scanAfterResponse(Product $product): void
    {
        app()->terminating(function () use ($product): void {
            try {
                app(ModerationService::class)->scan($product->fresh());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Moderatsiya tekshiruvi xatosi: {$e->getMessage()}", ['product_id' => $product->id]);
            }
        });
    }

    public function reportReasons()
    {
        return collect(ProductReport::REASONS)
            ->map(fn (string $id) => ['id' => $id, 'label' => __("messages.reasons.{$id}")]);
    }

    private function abortIfBlocked(Request $request): void
    {
        abort_if(
            $request->user()?->isBlocked(),
            403,
            __('messages.blocked'),
        );
    }
}