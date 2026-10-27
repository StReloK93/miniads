<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foydalanuvchilarning e'lon ustidan shikoyatlari (firibgarlik, sotilgan, noto'g'ri bo'lim...).
     * Bitta foydalanuvchi bitta e'longa bitta shikoyat: qayta yuborsa — yangilanadi.
     */
    public function up(): void
    {
        Schema::create('product_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 32);
            $table->string('comment', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'user_id']);
            $table->index(['resolved_at', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reports');
    }
};
