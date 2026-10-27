<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ko'p tillilik. Asosiy (o'zbekcha) matn o'z ustunida qoladi; ru/en tarjimalari — translations JSON'da:
     * {"ru": {"name": "..."}, "en": {"name": "..."}} (App\Models\Concerns\HasTranslations).
     */
    private const TABLES = ['categories', 'parameters', 'districts', 'price_types'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('translations')->nullable();
            });
        }

        // Bot xabarlari shu tilda yuboriladi (ilova X-Locale sarlavhasidan yangilanadi)
        Schema::table('users', function (Blueprint $table) {
            $table->string('language', 2)->default('uz')->after('phone');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('translations');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
