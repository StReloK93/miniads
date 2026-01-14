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
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('placeholder'); // Masalan: "Xonalar soni"
            $table->enum('type', ['string', 'number', 'boolean'])->default('string');
            $table->string('component');  // number, text, select
            $table->string('unit')->nullable(); // masalan: "kv/m"
            $table->json('options')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
