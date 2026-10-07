<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductViewService;
use App\Services\TelegramProductService;
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
        $this->telegramProductService->synchronize($product, $previouslyHadImage);

        return response()->json([
            'message' => "E'lon muvaffaqiyatli yangilandi!",
        ]);
    }

    public function show($id, ProductViewService $viewService)
    {
        $product = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->with(['user'])
            ->findOrFail($id);

        if (auth()->id() !== $product->user_id) {
            $viewService->record($product, auth()->id());
        }

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

    public function search(Request $request)
    {
        if (!$request->filled('search')) {
            return response()->json([]);
        }

        return Product::search($request->search)
            ->query(function (Builder $query) use ($request) {
                $query->active()
                    ->when($request->city_id, fn($q) => $q->where('district_id', $request->city_id))
                    ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
                    ->when($request->price_from, fn($q) => $q->where('price', '>=', $request->price_from))
                    ->when($request->price_to, fn($q) => $q->where('price', '<=', $request->price_to));
            })
            ->latest('published_at')
            ->get();
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