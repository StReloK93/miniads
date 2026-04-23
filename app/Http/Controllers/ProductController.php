<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
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
        try {
            $product = $this->productService->store($request);

            return response()->json([
                'message' => "E'lon joylandi!",
                'product_id' => $product->id,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->productService->update($request, $product);

            return response()->json([
                'message' => "E'lon muvaffaqiyatli yangilandi!",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->with(['user'])
            ->findOrFail($id);

        return response()->json($product);
    }

    public function latestTen()
    {
        return Product::with('category')
            ->withExists([
                'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
            ])
            ->active()
            ->latest()
            ->take(10)
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
                    ->when($request->city_id, fn($q) => $q->where('city_id', $request->city_id))
                    ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
                    ->when($request->price_from, fn($q) => $q->where('price', '>=', $request->price_from))
                    ->when($request->price_to, fn($q) => $q->where('price', '<=', $request->price_to));
            })
            ->get();
    }


    public function activate(Request $request, int $id)
    {
        $product = Product::with('category')->findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->productService->activate($product);

            return response()->json([
                'message' => "E'lon muvaffaqiyatli faollashtirildi!",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deActivate(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->productService->deActivate($product);

            return response()->json([
                'message' => "E'lon muvaffaqiyatli o'chirildi!",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    public function destroy(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        if (!$this->productService->isOwner($product, $request->user()->id)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $product->delete();

            return response()->json([
                'message' => "E'lon muvaffaqiyatli o'chirildi!",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}