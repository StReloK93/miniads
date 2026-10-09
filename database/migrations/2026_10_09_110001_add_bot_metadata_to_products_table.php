<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bot')->default(false)->after('user_id');
            $table->string('source_channel')->nullable()->after('is_bot');
            $table->unsignedBigInteger('source_message_id')->nullable()->after('source_channel');
            $table->string('source_hash')->nullable()->index()->after('source_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['source_hash']);
            $table->dropColumn(['is_bot', 'source_channel', 'source_message_id', 'source_hash']);
        });
    }
};
