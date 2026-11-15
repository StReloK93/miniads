<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vitrina — biznesning doimiy sahifasi (do'kon, menyu, portfolio, narxnoma, ko'chmas mulk).
 * E'londan farqi: muddati yo'q, ichida bo'limlar va ko'p mahsulot/ish bor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitrinas', function (Blueprint $table) {
            $table->id();
            // Bir foydalanuvchi — bitta vitrina
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique(); // ulashish havolasi: startapp=v_<slug>
            $table->string('type', 20)->default('shop'); // shop | menu | portfolio | pricelist | realty
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete(); // null — butun viloyat
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable(); // @username
            $table->string('instagram')->nullable();
            $table->string('work_hours')->nullable(); // "09:00–20:00", erkin matn
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->string('reject_reason')->nullable();
            $table->boolean('is_verified')->default(false); // "Tasdiqlangan" belgisi
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'district_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitrinas');
    }
};
