<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bir martalik tozalash:
 * - eski e'lonlardagi telefonlar (998901234567, +998 90 123 45 67...) — 90-123-45-67 ko'rinishiga;
 * - bot (AI) yozgan tavsiflardagi raqamlar (+99893 444 33 44) — 93-444-33-44 ko'rinishiga;
 * - narxsiz kategoriyadagi (kafe, restoran...) e'lonlarga AI qo'yib yuborgan narx — o'chiriladi ("Kelishiladi").
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'phone', 'description', 'is_bot'])
            ->chunkById(500, function ($products) {
                foreach ($products as $product) {
                    $changes = [];

                    $phone = Phone::format($product->phone);
                    if ($phone !== $product->phone) {
                        $changes['phone'] = $phone;
                    }

                    // Faqat bot e'lonlari: foydalanuvchi o'zi yozgan matnga tegmaymiz
                    if ($product->is_bot && filled($product->description)) {
                        $description = Phone::formatInText($product->description);
                        if ($description !== $product->description) {
                            $changes['description'] = $description;
                        }
                    }

                    if ($changes) {
                        DB::table('products')->where('id', $product->id)->update($changes);
                    }
                }
            });

        DB::table('products')
            ->whereNotNull('price')
            ->whereIn('category_id', DB::table('categories')->where('with_price', false)->select('id'))
            ->update(['price' => null]);

        DB::table('products')->where('price', 0)->update(['price' => null]);
    }

    public function down(): void
    {
        // Ma'lumotni qayta tiklab bo'lmaydi (eski format kerak emas)
    }
};
