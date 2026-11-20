<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Rasmlar tartibi: 0 — asosiy rasm (kartada, kanal postida birinchi chiqadi) */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedTinyInteger('sort')->default(0)->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('sort');
        });
    }
};
