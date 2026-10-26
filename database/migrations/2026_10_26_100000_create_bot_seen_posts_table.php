<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bot ko'rib chiqqan har bir Telegram post (natijasidan qat'i nazar) shu yerda saqlanadi,
     * shunda bir post AI'ga faqat bir marta yuboriladi.
     */
    public function up(): void
    {
        Schema::create('bot_seen_posts', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->unsignedBigInteger('message_id');
            $table->string('status', 30); // imported, rejected, filtered, duplicate, old
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['channel', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_seen_posts');
    }
};
