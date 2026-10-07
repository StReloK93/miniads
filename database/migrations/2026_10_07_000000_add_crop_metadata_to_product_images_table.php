<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('crop_src')->nullable();
            $table->unsignedTinyInteger('crop_x')->default(50);
            $table->unsignedTinyInteger('crop_y')->default(50);
            $table->decimal('crop_scale', 4, 2)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['crop_src', 'crop_x', 'crop_y', 'crop_scale']);
        });
    }
};
