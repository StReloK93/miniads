<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParameterValue;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ProductService
{

   public function isOwner(Product $product, int $userId): bool
   {
      return (int) $product->user_id === (int) $userId;
   }
   public function store(Request $request): Product
   {
      $request->merge([
         'user_id' => $request->user()->id,
         'district_id' => $request->user()->active_district_id,
      ]);

      $product = Product::create($request->all());

      $product->expires_at = now()->addDays($product->category->listing_duration_days);
      $product->save();

      if ($request->has('parameters')) {
         $this->syncProductParameters($product->id, $request->parameters);
      }

      $manager = ImageManager::usingDriver(Driver::class);

      if ($request->has('images')) {
         foreach ($request->file('images') as $image) {
            ProductImage::create([
               'product_id' => $product->id,
               'src' => $this->storeProductImage($image['file'], $manager),
            ]);
         }
      }

      return $product;
   }

   public function update(Request $request, Product $product): Product
   {
      $product->update($request->all());

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
}