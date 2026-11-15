<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vitrina mahsulotidan chiqarilgan e'lon: kartada "… vitrinasidan" belgisi va vitrinaga havola.
 * Vitrina o'chsa e'lon qoladi, faqat bog'lanish uziladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vitrina_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vitrina_id');
        });
    }
};
