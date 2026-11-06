<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Muddatsiz e'lon endi expires_at = null. Avval "2099-12-31" yozilardi — MySQL TIMESTAMP 2038 dan
 * katta sanani qabul qilmaydi va muddatsiz kategoriyaga e'lon qo'shishda xato chiqardi.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->default(null)->change();
        });

        // Muddatsiz kategoriyalardagi faol e'lonlar muddatsiz bo'ladi
        $permanentCategories = DB::table('categories')->where('is_permanent', true)->pluck('id');
        if ($permanentCategories->isNotEmpty()) {
            DB::table('products')
                ->whereIn('category_id', $permanentCategories)
                ->where('expires_at', '>=', now())
                ->update(['expires_at' => null]);
        }
    }

    public function down(): void
    {
        DB::table('products')->whereNull('expires_at')->update(['expires_at' => '2038-01-01 00:00:00']);

        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('expires_at')->useCurrent()->change();
        });
    }
};
