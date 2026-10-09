<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedTinyInteger('hourly_limit')->default(7);
            $table->unsignedSmallInteger('daily_limit')->default(150);
            $table->text('channels')->nullable();
            $table->boolean('telegram_post_enabled')->default(true);
            $table->unsignedTinyInteger('telegram_start_hour')->default(8);
            $table->unsignedTinyInteger('telegram_end_hour')->default(23);
            $table->string('ai_provider')->default('gemini');
            $table->text('ai_api_key')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedSmallInteger('today_imported_count')->default(0);
            $table->date('today_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
