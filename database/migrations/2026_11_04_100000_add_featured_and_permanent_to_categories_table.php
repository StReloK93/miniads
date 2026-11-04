<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // E'lonlari hech qachon tugamaydi (masalan Kafe-restoranlar)
            $table->boolean('is_permanent')->default(false)->after('listing_duration_days');
            // Bosh sahifadagi "Tezkor bo'limlar" qatorida chiqadi; tartibi featured_order bo'yicha
            $table->boolean('is_featured')->default(false)->after('is_permanent');
            $table->unsignedSmallInteger('featured_order')->default(0)->after('is_featured');
            // O'chirilsa formada sarlavha so'ralmaydi — server uni parametrlardan yasaydi (masalan Naqd-Plastik)
            $table->boolean('with_title')->default(true)->after('with_image');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['is_permanent', 'is_featured', 'featured_order', 'with_title']);
        });
    }
};
