<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Avval "search_logs_and_settings" migratsiyasi bilan yaratilgan serverlarda jadval allaqachon bor
        if (Schema::hasTable('settings')) {
            return;
        }

        // Oddiy kalit-qiymat sozlamalar (taqiqlangan so'zlar va h.k.)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
