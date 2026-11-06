<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E'lonni to'xtatayotganda egasi "Sotildi" deb belgilasa — vaqti. Qayta faollashtirilsa tozalanadi.
 * Platforma orqali nechta narsa sotilayotganini bilish uchun (admin bosh sahifasi).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('sold_at')->nullable()->after('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sold_at');
        });
    }
};
