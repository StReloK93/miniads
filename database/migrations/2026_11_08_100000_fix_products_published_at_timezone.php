<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Yangi e'lonlarning published_at'i MySQL CURRENT_TIMESTAMP (server zonasi) bilan yozilgan edi va
     * created_at'dan bir necha soat oldin chiqib qolgan. E'lon yaratilishidan oldin chop etilgan bo'lolmaydi —
     * shundaylarini created_at'ga tenglaymiz. Qayta faollashtirilganlar (published_at > created_at) tegilmaydi.
     */
    public function up(): void
    {
        DB::table('products')
            ->whereColumn('published_at', '<', 'created_at')
            ->update(['published_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        //
    }
};
