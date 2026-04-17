<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParameterValue;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
class ProductController extends Controller
{
    public function index()
    {
        return Product::withExists([
            'favorites as is_favorite' => fn($q) =>
                $q->where('user_id', auth()->id())
        ])->active()->all();
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        $request->merge([
            'user_id' => $request->user()->id,
            'district_id' => $request->user()->active_district_id,
        ]);

        try {
            $product = Product::create($request->all());

            $product->expires_at = now()->addDays($product->category->listing_duration_days);
            $product->save();

            if ($request->has('parameters')) {
                foreach ($request->parameters as $param) {
                    if (($param['id'] ?? null) !== null) {
                        ProductParameterValue::create([
                            'product_id' => $product->id,
                            'parameter_id' => $param['id'],
                            'value' => $param['value'] ?? null,
                        ]);
                    }
                }
            }

            $manager = ImageManager::usingDriver(Driver::class);

            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {

                    $filename = Str::uuid() . '.webp';
                    $path = 'products/' . $filename;

                    $encoded = $manager
                        ->decode($image['file'])
                        ->scaleDown(width: 800);

                    $encoded->save(public_path('storage/' . $path), 80, 'webp');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'src' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => "E'lon muvaffaqiyatli joylandi!",
                'product_id' => $product->id,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $product->update($request->all());

            $this->syncProductParameters(
                $product->id,
                $request->input('parameters', [])
            );

            $this->syncProductImages(
                $product,
                $request->all()['images'] ?? []
            );

            DB::commit();

            return response()->json([
                'message' => "E'lon muvaffaqiyatli yangilandi!",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function syncProductParameters(int $productId, array $parameters): void
    {
        foreach ($parameters as $param) {
            $parameterId = $param['id'] ?? null;

            if (!$parameterId) {
                continue;
            }

            ProductParameterValue::updateOrCreate(
                [
                    'product_id' => $productId,
                    'parameter_id' => $parameterId,
                ],
                [
                    'value' => $param['value'] ?? null,
                ]
            );
        }
    }

    private function syncProductImages(Product $product, array $images): void
    {
        $manager = ImageManager::usingDriver(Driver::class);

        $existingImageIds = collect($images)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values();

        $validKeptImageIds = $product->images()
            ->whereIn('id', $existingImageIds)
            ->pluck('id');

        $imagesToDelete = $product->images()
            ->whereNotIn('id', $validKeptImageIds)
            ->get();

        foreach ($imagesToDelete as $image) {
            $this->deleteProductImageFile($image->src);
            $image->delete();
        }

        foreach ($images as $image) {
            if (!empty($image['id'])) {
                continue;
            }

            $uploadedFile = $image['file'] ?? null;

            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $path = $this->storeProductImage($uploadedFile, $manager);

            ProductImage::create([
                'product_id' => $product->id,
                'src' => $path,
            ]);
        }
    }

    private function storeProductImage(UploadedFile $file, ImageManager $manager): string
    {
        $filename = Str::uuid() . '.webp';
        $path = 'products/' . $filename;

        $encoded = $manager
            ->decode($file)
            ->scaleDown(width: 800);

        $encoded->save(public_path('storage/' . $path), 80, 'webp');

        return $path;
    }

    private function deleteProductImageFile(?string $src): void
    {
        if (!$src) {
            return;
        }

        $fullPath = public_path('storage/' . ltrim($src, '/'));

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    public function show($id)
    {
        $product = Product::withExists([
            'favorites as is_favorite' => fn($q) =>
                $q->where('user_id', auth()->id())
        ])->with(['user'])->findOrFail($id);
        return response()->json($product);
    }


    public function latestTen()
    {
        return Product::with('category')->withExists([
            'favorites as is_favorite' => fn($q) =>
                $q->where('user_id', auth()->id())
        ])->active()->latest()->take(10)->get();
    }


    public function myAds(Request $request, $status)
    {
        $query = Product::where('user_id', $request->user()->id)
            ->latest();

        if ($status === 'active') {
            $query->active();
        } else if ($status === 'expired') {
            $query->passive();
        }

        return $query->get();
    }

    public function search(Request $request)
    {

        if (!$request->filled('search')) {
            return response()->json([]);
        }

        return Product::search($request->search)
            ->query(function (\Illuminate\Database\Eloquent\Builder $query) use ($request) {
                $query->active();

                if ($request->city_id) {
                    $query->where('city_id', $request->city_id);
                }

                if ($request->category_id) {
                    $query->where('category_id', $request->category_id);
                }

                if ($request->price_from) {
                    $query->where('price', '>=', $request->price_from);
                }

                if ($request->price_to) {
                    $query->where('price', '<=', $request->price_to);
                }
            })
            ->get();
    }

}
