<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foydalanuvchilar faolligi kunlik yig'indisi (xom hodisalar storage/app/activity/*.log da)
        Schema::create('activity_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('event', 32);
            $table->string('ref', 64)->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('users')->default(0);

            $table->index(['date', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_stats');
    }
};
