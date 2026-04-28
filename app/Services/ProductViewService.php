<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductViewService
{
   public function record(Product $product, ?int $userId = null): void
   {
      $viewerKey = $userId
         ? "user:$userId"
         : "guest:" . request()->ip();

      // Har bir user/guest bitta productni 1 kunda faqat 1 marta view qiladi
      $seenKey = "product:viewed:{$product->id}:{$viewerKey}:" . now()->format('Y-m-d');

      if (Cache::has($seenKey)) {
         return;
      }

      Cache::put($seenKey, true, now()->addDay());

      // Product bo‘yicha yig‘ilgan view soni
      Cache::increment("product:views:{$product->id}");

      // Qaysi productlarda view borligini eslab qolamiz
      $pendingIds = Cache::get('product:views:pending_ids', []);

      if (!in_array($product->id, $pendingIds)) {
         $pendingIds[] = $product->id;
         Cache::put('product:views:pending_ids', $pendingIds, now()->addDays(2));
      }
   }

   public function flush(): void
   {
      $ids = Cache::get('product:views:pending_ids', []);

      if (empty($ids)) {
         return;
      }

      foreach ($ids as $productId) {
         $key = "product:views:$productId";
         $count = (int) Cache::pull($key, 0);

         if ($count <= 0) {
            continue;
         }

         Product::where('id', $productId)->increment('views_count', $count);
      }

      Cache::forget('product:views:pending_ids');
   }
}