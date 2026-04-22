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

            $table->foreignId('price_type_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamp('expires_at')->default(now()); // E'lonning amal qilish muddati
            $table->timestamps();
            $table->softDeletes();
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
