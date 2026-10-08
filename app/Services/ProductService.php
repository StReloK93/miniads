<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParameterValue;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ProductService
{
    private const MAX_IMAGE_DIMENSION = 1024;

    public function isOwner(Product $product, int $userId): bool
    {
        return (int) $product->user_id === (int) $userId;
    }

    public function store(Request $request): Product
    {
        $product = Product::create([
            ...$request->only([
                'title',
                'description',
                'category_id',
                'district_id',
                'phone',
                'price',
                'price_type_id',
                'back_color_id',
            ]),
            'user_id' => $request->user()->id,
        ]);

        $product->expires_at = now()->addDays($product->category->listing_duration_days);
        $product->save();

        if ($request->has('parameters')) {
            $this->syncProductParameters($product->id, $request->parameters);
        }

        foreach ($request->file('images', []) as $index => $image) {
            $uploadedFile = is_array($image) ? ($image['file'] ?? null) : $image;

            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $crop = $this->normalizeCrop($request->input("images.{$index}", []));
            $src = $this->storeProductImage($uploadedFile);

            ProductImage::create([
                'product_id' => $product->id,
                'src' => $src,
                ...$crop,
            ]);
        }

        return $product;
    }

    public function update(Request $request, Product $product): Product
    {
        $productAttributes = $request->only([
            'title',
            'description',
            'category_id',
            'district_id',
            'phone',
            'price',
            'price_type_id',
            'back_color_id',
        ]);
        $productAttributes['district_id'] = ($productAttributes['district_id'] ?? null) != 0
            ? ($productAttributes['district_id'] ?? null)
            : null;

        if (empty($productAttributes['category_id'])) {
            unset($productAttributes['category_id']);
        }

        $product->update($productAttributes);

        $this->syncProductParameters(
            $product->id,
            $request->input('parameters', [])
        );

        $this->syncProductImages(
            $product,
            $request->all()['images'] ?? []
        );

        return $product;
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
            $this->deleteProductImageFile($image->crop_src);
            $image->delete();
        }

        foreach ($images as $image) {
            if (!empty($image['id'])) {
                $existingImage = $product->images()->find($image['id']);

                if ($existingImage && $this->hasCropData($image)) {
                    $crop = $this->normalizeCrop($image);

                    if (
                        !empty($image['crop_changed'])
                        || $existingImage->crop_x !== $crop['crop_x']
                        || $existingImage->crop_y !== $crop['crop_y']
                        || (float) $existingImage->crop_scale !== $crop['crop_scale']
                    ) {
                        $this->deleteProductImageFile($existingImage->crop_src);
                        $existingImage->update([
                            'crop_src' => null,
                            ...$crop,
                        ]);
                    }
                }

                continue;
            }

            $uploadedFile = $image['file'] ?? null;

            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $crop = $this->normalizeCrop($image);
            $path = $this->storeProductImage($uploadedFile);

            ProductImage::create([
                'product_id' => $product->id,
                'src' => $path,
                ...$crop,
            ]);
        }
    }

    private function hasCropData(array $image): bool
    {
        return array_key_exists('crop_x', $image)
            || array_key_exists('crop_y', $image)
            || array_key_exists('crop_scale', $image);
    }

    private function normalizeCrop(array $crop): array
    {
        return [
            'crop_x' => max(0, min(100, (int) ($crop['crop_x'] ?? 50))),
            'crop_y' => max(0, min(100, (int) ($crop['crop_y'] ?? 50))),
            'crop_scale' => max(1, min(3, (float) ($crop['crop_scale'] ?? 1))),
        ];
    }

    private function storeProductImage(UploadedFile $file): string
    {
        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \InvalidArgumentException("Rasm formati qo'llab-quvvatlanmaydi.");
        }

        $filename = Str::uuid() . '.webp';
        $path = 'products/' . $filename;
        $destination = public_path('storage/' . $path);

        File::ensureDirectoryExists(dirname($destination));

        ImageManager::usingDriver(Driver::class)
            ->decode($file->getRealPath())
            ->scaleDown(self::MAX_IMAGE_DIMENSION, self::MAX_IMAGE_DIMENSION)
            ->encode(new WebpEncoder(quality: 82, strip: true))
            ->save($destination);

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

    public function activate(Product $product): Product
    {
        $product->expires_at = now()->addDays($product->category->listing_duration_days);
        $product->published_at = now();
        $product->save();

        return $product;
    }

    public function deActivate(Product $product): Product
    {
        $product->expires_at = now()->subMinute();
        $product->save();

        return $product;
    }
}
