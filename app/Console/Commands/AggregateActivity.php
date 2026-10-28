<?php

namespace App\Console\Commands;

use App\Services\ActivityLog;
use Illuminate\Console\Command;

class AggregateActivity extends Command
{
    protected $signature = 'activity:aggregate';

    protected $description = "Faollik fayllarini (bugun va kecha) kunlik statistikaga yig'ish";

    public function handle(): int
    {
        // Kecha ham: yarim tundan oldingi oxirgi daqiqalar yig'ilmay qolmasin
        foreach ([now()->subDay()->toDateString(), now()->toDateString()] as $date) {
            $rows = ActivityLog::aggregate($date);
            $this->info("{$date}: {$rows} qator");
        }

        ActivityLog::pruneRawFiles();

        return self::SUCCESS;
    }
}
