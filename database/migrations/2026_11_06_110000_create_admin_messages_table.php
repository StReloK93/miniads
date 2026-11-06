<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin e'lon muallifiga yozgan ogohlantirish/tushuntirishlar.
 * Bot orqali yuboriladi va ilovada ham ko'rsatiladi (botni ishga tushirmaganlarga ham yetib borsin).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Qaysi e'lon haqida (ixtiyoriy — muallifning o'ziga umumiy xabar ham bo'ladi)
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16)->default('info'); // warning | info
            $table->text('body');
            $table->boolean('telegram_sent')->default(false);
            // Foydalanuvchi ilovada "Tushunarli" bosgan vaqt
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_messages');
    }
};
