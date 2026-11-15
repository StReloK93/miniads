<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vitrinadagi mahsulot / taom / ish / xizmat / obyekt */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitrina_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vitrina_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vitrina_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // fixed — aniq narx, from — "...dan boshlab", negotiable — kelishiladi, none — narxsiz (portfolio)
            $table->string('price_mode', 20)->default('fixed');
            $table->bigInteger('price')->nullable();
            $table->foreignId('price_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit', 30)->nullable(); // "kv.m", "kg", "porsiya", "soat"
            $table->json('images')->nullable(); // ["vitrinas/items/uuid.webp", ...]
            $table->boolean('is_available')->default(true); // "Tugagan" / "Hozir yo'q"
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitrina_items');
    }
};
