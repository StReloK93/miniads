<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->string('src');
            // Ro'yxat kartalari uchun kichik nusxa (App\Support\ProductThumbnail; eskilari: php artisan products:thumbnails)
            $table->string('thumb_src')->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // Kartadagi ko'rinish (kesish): markaz foizda va kattalashtirish
            $table->string('crop_src')->nullable();
            $table->unsignedTinyInteger('crop_x')->default(50);
            $table->unsignedTinyInteger('crop_y')->default(50);
            $table->decimal('crop_scale', 4, 2)->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
