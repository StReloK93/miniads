<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::command('products:flush-views')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('activity:aggregate')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('ads:import-telegram')
    ->hourly()
    ->withoutOverlapping(60);

// Bot bildirishnomalari
Schedule::command('notifications:saved-searches')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30);

Schedule::command('notifications:expiring')
    ->dailyAt('10:00')
    ->withoutOverlapping();