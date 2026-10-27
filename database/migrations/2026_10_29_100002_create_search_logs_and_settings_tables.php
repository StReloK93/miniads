<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin statistikasi: nima qidirilyapti va nima topilmayapti (talab bor, taklif yo'q)
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 120);
            $table->unsignedInteger('results_count');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['query', 'created_at']);
        });

        // Oddiy kalit-qiymat sozlamalar (taqiqlangan so'zlar va h.k.)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('settings');
    }
};
