<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bot obunachilariga (bot xabar yubora oladiganlarga) admin yuborgan postlar va ularning hisobi:
 * nechtasiga yetdi, nechtasiga yetmadi (botni to'xtatgan va h.k.).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('text');
            $table->text('html');
            // Rasm: birinchi yuborishda Telegram'ga yuklanadi, keyin file_id bilan (qayta yuklanmaydi)
            $table->string('photo_path')->nullable();
            $table->string('photo_file_id')->nullable();
            $table->string('button_text', 64)->nullable();
            $table->string('button_url', 512)->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
