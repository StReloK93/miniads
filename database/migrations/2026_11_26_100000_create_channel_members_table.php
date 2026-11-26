<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Foydalanuvchi qaysi kanal(lar)ga a'zo — admin panelda "kanal / bot / ikkalasi" ko'rinishi uchun.
// Telegram kanal a'zolari ro'yxatini bermaydi: har foydalanuvchi getChatMember bilan tekshiriladi
// (users:check-channels) va kanal "chat_member" yangilanishlari kelsa darhol yoziladi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // config('services.telegram.channels') dagi qiymat (-100... yoki @username)
            $table->string('chat_id', 64);
            // member / administrator / creator / restricted / left / kicked
            $table->string('status', 20);
            $table->boolean('is_member')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'chat_id']);
            $table->index(['chat_id', 'is_member']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Oxirgi tekshiruv: eng eski tekshirilganlar birinchi navbatda qayta tekshiriladi
            $table->timestamp('channels_checked_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('channels_checked_at');
        });
        Schema::dropIfExists('channel_members');
    }
};
