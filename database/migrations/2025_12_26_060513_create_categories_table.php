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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Masalan: "Elektronika" yoki "Telefonlar"
            $table->string('image')->nullable(); // Faqat asosiy kategoriyalar uchun
            $table->unsignedBigInteger('parent_id')->nullable(); // Shu joyi muhim!
            $table->boolean('is_page')->default(false); // Faqat asosiy kategoriyalar uchun

            $table->softDeletes();
            // O'z-o'ziga bog'lanish (Self-referencing)
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
