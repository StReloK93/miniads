<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sevimlilardagi e'lon arzonlasa bot xabar beradi (Profil → Bildirishnomalar'da o'chirsa bo'ladi) */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_price_drop')->default(true)->after('notify_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notify_price_drop');
        });
    }
};
