<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vitrina bo'limlari: "Divanlar", "Birinchi taomlar", "Ishlarim", "Narxlar"... */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitrina_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vitrina_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitrina_sections');
    }
};
