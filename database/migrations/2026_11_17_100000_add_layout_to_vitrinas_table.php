<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mahsulotlar ko'rinishi: grid — rasmli plitkalar, list — matnli ro'yxat.
// null — egasi tanlamagan: vitrina turidan olinadi (do'kon — plitka, menyu — ro'yxat...)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vitrinas', function (Blueprint $table) {
            $table->string('layout', 10)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('vitrinas', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
