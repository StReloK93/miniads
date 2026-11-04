<?php

use App\Models\Category;
use Database\Seeders\FeaturedCategorySeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Tezkor bo'limlar kategoriyalari (Kafe-Restoranlar, Naqd-Plastik, Yo'lovchilar, Xizmatlar → Uy xizmatlari).
 * Faqat kategoriyalari bor bazaga qo'shiladi; bo'sh bazada (yangi o'rnatish, testlar) DatabaseSeeder o'zi chaqiradi.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Category::exists()) {
            (new FeaturedCategorySeeder())->run();
        }
    }

    public function down(): void
    {
        (new FeaturedCategorySeeder())->rollback();
    }
};
