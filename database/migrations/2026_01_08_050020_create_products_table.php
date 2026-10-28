<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // Faqat is_page = true bo'lganlar
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Telegram kanallardan avtomatik import qilingan e'lonlar (ads:import-telegram)
            $table->boolean('is_bot')->default(false);
            $table->string('source_channel')->nullable();
            $table->unsignedBigInteger('source_message_id')->nullable();
            $table->string('source_hash')->nullable();

            $table->string('title'); // E'lon sarlavhasi
            $table->text('description')->nullable(); // Batafsil ma'lumot
            $table->bigInteger('back_color_id')->default(1); // Rasm(lar) uchun JSON formatida saqlanadi
            // Manzil (Navoiy viloyati tumanlari uchun)
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();// Masalan: Uchquduq, Zarafshon, Navoiy sh.
            // Aloqa
            $table->string('phone')->nullable(); // Telefon raqam
            // Holati
            $table->integer('views_count')->default(0); // Ko'rishlar soni
            $table->bigInteger('price')->nullable(); // Narxi
            $table->boolean('contract_price')->default(false); // Narxi

            $table->foreignId('price_type_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamp('expires_at')->useCurrent(); // E'lonning amal qilish muddati
            // Qaysi muddat uchun eslatildi: e'lon uzaytirilsa expires_at o'zgaradi va yana eslatiladi
            $table->timestamp('expiry_reminded_for')->nullable();
            $table->timestamp('published_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Bir xil e'lon qayta import qilinmasin (BotAdImportService)
            $table->index('source_hash');
            // "Faol e'lon" = expires_at >= now(): kategoriya bo'yicha soni/ro'yxati va lenta
            $table->index(['category_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('products');
    }
};
