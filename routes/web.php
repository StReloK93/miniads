<?php

use Illuminate\Support\Facades\Route;



Route::get('/admin', function () {
    return view('admin');
});

Route::prefix('admin')->group(function () {
    Route::get('/{any}', function () {
        return view('admin');
    })->where('any', '.*');
});

// Maxfiylik siyosati va qoidalar: oddiy sahifa (BotFather / Telegram Ads moderatorlari ham ochadi)
Route::view('/privacy', 'privacy')->name('privacy');

// E'lon sahifasi: o'sha ilova, lekin havola kartasi (rasm, sarlavha, narx) shu e'londan
Route::get('/product/{id}', fn (int $id) => view('app', ['meta' => \App\Support\PageMeta::forProduct($id)]))
    ->whereNumber('id');

// api/ — bu yerga tushmaydi: noma'lum API manzili HTML sahifa emas, JSON 404 qaytarsin
// (aks holda keshlangan route'lar eskirganda admin panel HTML'ni ma'lumot deb o'qib yiqiladi)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api(/|$)).*');