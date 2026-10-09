<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductViewService;
use App\Services\TelegramProductService;
use App\Services\ProductSearchService;
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

    public function store(Request $request)
    {
        $product = $this->productService->store($request);
        $this->telegramProductService->publish($product);

        return response()->json([
            'message' => "E'lon joylandi!",
            'product_id' => $product->id,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $previouslyHadImage = $product->images->isNotEmpty();
        $this->productService->update($request, $product);

        try {
            $this->telegramProductService->synchronize($product, $previouslyHadImage);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Telegram sinxronizatsiya xatosi: " . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e,
            ]);
        }

        return response()->json([
            'message' => "E'lon muvaffaqiyatli yangilandi!",
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
                'message' => "Ushbu e'lon mavjud emas yoki muddati tugagan.",
            ], 404);
        }

        $user = auth()->user();
        $isOwnerOrAdmin = $user && ($user->id === $product->user_id || $user->role === 'admin');
        if ($product->expires_at && $product->expires_at < now() && !$isOwnerOrAdmin) {
            return response()->json([
                'message' => "Ushbu e'lon mavjud emas yoki muddati tugagan.",
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

        return response()->json($product);
    }


    public function edit(int $id)
    {
        return Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->with(['user'])
            ->findOrFail($id);
    }

    public function latestTen()
    {
        return Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->when(auth()->user()->active_district_id, function ($q) {
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

        $this->productService->activate($product);

        return response()->json([
            'message' => "E'lon muvaffaqiyatli faollashtirildi!",
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
            'message' => "E'lon muvaffaqiyatli o'chirildi!",
        ]);
    }





    public function destroy(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $product->delete();

        return response()->json([
            'message' => "E'lon muvaffaqiyatli o'chirildi!",
        ]);
    }
}