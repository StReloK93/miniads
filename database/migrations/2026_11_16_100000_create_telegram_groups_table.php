<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bot "kanalga a'zo bo'lmasa yozolmaydi" nazoratini olib boradigan guruhlar.
// Faqat ilova admini botni qo'shgan guruhlar yoziladi — begona guruhlardan bot o'zi chiqib ketadi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_groups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->unique();
            $table->string('title')->nullable();
            $table->bigInteger('added_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_groups');
    }
};
