<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * "Faol e'lon" = expires_at >= now(). Bu shart deyarli har bir ro'yxatda bor:
     * kategoriya bo'yicha soni/ro'yxati (category_id + expires_at) va lenta (expires_at).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'expires_at']);
            $table->dropIndex(['expires_at']);
        });
    }
};
