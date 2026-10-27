<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qidiruvni kuzatish: mos yangi e'lon chiqsa bot xabar yuboradi (notifications:saved-searches).
     */
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query', 120);
            // filters — ilovadagi filtr oynasi holati (qayta ochish uchun), params — server filtrlari (ProductFilters)
            $table->json('filters')->nullable();
            $table->json('params')->nullable();
            $table->string('label', 200);
            $table->boolean('is_paused')->default(false);
            // Shu id'dan kattalari "yangi" hisoblanadi
            $table->unsignedBigInteger('last_product_id')->default(0);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
