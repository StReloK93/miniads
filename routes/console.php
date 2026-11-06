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

// Kanaldagi qadalgan "jonli" post: tahrirlanadi (bildirishnomasiz), raqamlar o'zgarmasa so'rov ketmaydi
Schedule::command('channel:live-post')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

// Fon vazifalari (e'lonni kanalga chiqarish, narx tushgani haqida xabar...): har daqiqa ~55 soniya ishlaydigan ishchi.
// Alohida supervisor kerak emas — scheduler o'zi ishga tushiradi; withoutOverlapping ikkinchisini ochmaydi
Schedule::command('queue:work --sleep=1 --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground();

// Eski arxiv va muddati ancha o'tgan e'lonlar rasmlari bilan o'chiriladi (disk to'lmasin) — tunda
Schedule::command('products:prune')
    ->dailyAt('04:00')
    ->withoutOverlapping(60);
