<?php

namespace App\Console\Commands;

use App\Services\ProductViewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FlushProductViews extends Command
{
    protected $signature = 'products:flush-views';

    protected $description = 'Flush cached product views to database';

    public function handle(ProductViewService $viewService): int
    {
        $viewService->flush();

        Log::info('Product views flushed by scheduler', [
            'time' => now()->toDateTimeString(),
        ]);
        $this->info('Product views flushed.');

        return self::SUCCESS;
    }
}