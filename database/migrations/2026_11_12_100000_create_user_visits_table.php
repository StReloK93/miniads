<?php

use App\Services\ActivityLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Foydalanuvchi ilovaga kirgan kunlar (bir kunda bir qator). Admin "bir marta kirib qaytmagan"
 * va "qayta-qayta kirayotgan" foydalanuvchilarni shundan ajratadi. Yangi kunlarni ActivityLog::aggregate yozadi.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_visits', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->primary(['user_id', 'date']);
            $table->index('date');
        });

        // Oldingi kunlarni tiklash: ro'yxatdan o'tgan kun (foydalanuvchi faqat ilovaga kirganda yaratiladi),
        // sessiya tokenlari ochilgan va oxirgi ishlatilgan kunlar
        $dates = DB::table('users')->selectRaw('id as user_id, DATE(created_at) as date')->whereNotNull('created_at')
            ->union(DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')
                ->selectRaw('tokenable_id as user_id, DATE(created_at) as date')->whereNotNull('created_at'))
            ->union(DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')
                ->selectRaw('tokenable_id as user_id, DATE(last_used_at) as date')->whereNotNull('last_used_at'))
            ->get();

        $userIds = DB::table('users')->pluck('id')->flip();
        $rows = $dates
            ->filter(fn ($row) => isset($userIds[$row->user_id]))
            ->map(fn ($row) => ['user_id' => (int) $row->user_id, 'date' => (string) $row->date])
            ->all();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_visits')->insertOrIgnore($chunk);
        }

        // Saqlanib qolgan (oxirgi ~14 kun) faollik fayllari: har kun qaytadan yig'iladi
        if (! app()->runningUnitTests()) {
            foreach (File::glob(storage_path('app/activity/*.log')) as $file) {
                ActivityLog::aggregate(basename($file, '.log'));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_visits');
    }
};
