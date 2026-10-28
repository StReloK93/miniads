<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Avval "search_logs_and_settings" migratsiyasi bilan yaratilgan serverlarda jadval allaqachon bor
        if (Schema::hasTable('search_logs')) {
            return;
        }

        // Admin statistikasi: nima qidirilyapti va nima topilmayapti (talab bor, taklif yo'q)
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 120);
            $table->unsignedInteger('results_count');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['query', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
