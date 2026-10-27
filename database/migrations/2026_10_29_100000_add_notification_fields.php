<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bot orqali bildirishnomalar: foydalanuvchi sozlamalari va muddat eslatmasi belgisi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Bot faqat ruxsat bergan (yoki /start bosgan) foydalanuvchiga yoza oladi
            $table->boolean('bot_can_message')->default(false)->after('blocked_at');
            $table->boolean('notify_saved_searches')->default(true)->after('bot_can_message');
            $table->boolean('notify_expiry')->default(true)->after('notify_saved_searches');
            // Yangi e'lon formasida avtomatik to'ldiriladigan raqam
            $table->string('phone', 32)->nullable()->after('notify_expiry');
        });

        Schema::table('products', function (Blueprint $table) {
            // Qaysi muddat uchun eslatildi: e'lon uzaytirilsa expires_at o'zgaradi va yana eslatiladi
            $table->timestamp('expiry_reminded_for')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bot_can_message', 'notify_saved_searches', 'notify_expiry', 'phone']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('expiry_reminded_for');
        });
    }
};
