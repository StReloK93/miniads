<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TelegramAuth;
use App\Http\Middleware\OptionalSanctumAuth;

Route::get('/user', function (Request $request) {
    return $request->user()->load('activeDistrict');
})->middleware('auth:sanctum');

Route::post('/telegram/sign-in', [AuthController::class, 'telegramSignIn'])->middleware(TelegramAuth::class);
Route::post('/telegram/widget-sign-in', [AuthController::class, 'telegramWidgetAuth']);


// Nom Telegraph paketining "telegraph.webhook" route'idan farq qilishi shart — aks holda `php artisan optimize` (route:cache) xato beradi
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

if (app()->environment('local')) {
    Route::post('/test-auth', [AuthController::class, 'testAuth']);
}


Route::controller(App\Http\Controllers\CategoryController::class)->group(function () {
    Route::get('/categories/parents/{id?}', 'parents');
    Route::get('/categories/counts', 'counts');
    Route::get('categories/{id}/products', 'products')->middleware(OptionalSanctumAuth::class);
});

// E'lonlarni ko'rish ro'yxatdan o'tmagan foydalanuvchilar uchun ham ochiq
Route::middleware(OptionalSanctumAuth::class)->controller(App\Http\Controllers\ProductController::class)->group(function () {
    Route::get('/products/custom/feed', 'feed');
    Route::get('/products/custom/latest_ten', 'latestTen');
    Route::get('/products/custom/search', 'search');
    Route::get('/products/{id}', 'show')->whereNumber('id');
});
// Qidiruv statistikasi (mehmonlar ham): ilova so'rov "o'rnashgan"da bir marta yuboradi
Route::post('/search-log', [App\Http\Controllers\ProfileController::class, 'logSearch'])
    ->middleware([OptionalSanctumAuth::class, 'throttle:30,1']);
// Ilovadagi xatolar (forma yuborilmadi, JS xatosi) — admin chatiga ogohlantirish
Route::post('/client-errors', [App\Http\Controllers\ClientErrorController::class, 'store'])
    ->middleware([OptionalSanctumAuth::class, 'throttle:20,1']);
// Faollik statistikasi: ilova ~30 soniyada bir paket yuboradi (ilova ochilishi, sahifalar, aloqa tugmalari)
Route::post('/activity', [App\Http\Controllers\ActivityController::class, 'store'])
    ->middleware([OptionalSanctumAuth::class, 'throttle:30,1']);
Route::apiResource('categories', App\Http\Controllers\CategoryController::class)->only('index', 'show');
Route::apiResource('parameters', App\Http\Controllers\ParameterController::class)->only('index', 'show');
Route::get('categories/{category}/parameters', [App\Http\Controllers\CategoryParameterController::class, 'index']);
Route::get('districts', [App\Http\Controllers\DistrictController::class, 'index']);
Route::get('price-types', [App\Http\Controllers\PriceTypeController::class, 'index']);
Route::get('price-types/{id}', [App\Http\Controllers\PriceTypeController::class, 'show']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::prefix('admin')->controller(App\Http\Controllers\AdminController::class)->group(function () {
        Route::get('dashboard', 'dashboard');
        Route::get('products', 'products');
        Route::patch('products/{id}/status', 'updateProductStatus');
        Route::delete('products/{id}', 'deleteProduct');
        Route::post('products/{id}/restore', 'restoreProduct');
        Route::delete('products/{id}/force', 'forceDeleteProduct');
        Route::post('products/{id}/reports/resolve', 'resolveReports');
        Route::post('products/bulk', 'bulkProducts');
        Route::get('search-stats', 'searchStats');
        Route::get('activity', 'activity');
        Route::get('moderation', 'moderationSettings');
        Route::post('moderation', 'updateModerationSettings');
        Route::get('users', 'users');
        Route::patch('users/{id}/role', 'updateUserRole');
        Route::post('users/{id}/block', 'blockUser');
        Route::post('users/{id}/unblock', 'unblockUser');
        Route::get('bot-settings', 'getBotSettings');
        Route::post('bot-settings', 'updateBotSettings');
        Route::post('bot-settings/run-now', 'runBotImport');
        Route::post('bot-import-url', 'importBotPost');
        Route::get('channel-post', 'channelPostDefaults');
        Route::post('channel-post', 'sendChannelPost');
    });

    Route::controller(App\Http\Controllers\CategoryController::class)->group(function () {
        Route::post('/categories/change_parent/{id}', 'changeParent');
        Route::delete('categories/{id}/force', 'forceDelete');
        Route::post('categories/{id}', 'update');
        Route::post('categories', 'store');
        Route::delete('categories/{id}', 'destroy');
    });
    Route::apiResource('parameters', App\Http\Controllers\ParameterController::class)->only('store', 'update', 'destroy');
    Route::apiResource('categories.parameters', App\Http\Controllers\CategoryParameterController::class)->only('store');
    Route::apiResource('districts', App\Http\Controllers\DistrictController::class)->only('store', 'update', 'destroy');
    Route::apiResource('price-types', App\Http\Controllers\PriceTypeController::class)->except('index');
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/back-colors', [App\Http\Controllers\BackColorController::class, 'index']);

    Route::get('/products/custom/my_ads/{status}', [App\Http\Controllers\ProductController::class, 'myAds']);
    Route::post('/products/{id}/deactivate', [App\Http\Controllers\ProductController::class, 'deactivate']);
    Route::post('/products/{id}/activate', [App\Http\Controllers\ProductController::class, 'activate']);
    Route::post('/products/{id}/report', [App\Http\Controllers\ProductController::class, 'report'])->middleware('throttle:10,1');
    Route::get('/report-reasons', [App\Http\Controllers\ProductController::class, 'reportReasons']);

    Route::controller(App\Http\Controllers\ProfileController::class)->group(function () {
        Route::get('/profile', 'show');
        Route::patch('/profile', 'update');
        Route::post('/profile/write-access', 'writeAccess');
    });
    Route::apiResource('saved-searches', App\Http\Controllers\SavedSearchController::class)->except('update');
    Route::patch('saved-searches/{id}', [App\Http\Controllers\SavedSearchController::class, 'update']);
    Route::post('/products/{id}', [App\Http\Controllers\ProductController::class, 'update']);
    Route::get('/products/{id}/edit', [App\Http\Controllers\ProductController::class, 'edit']);


    Route::apiResource('favorites', App\Http\Controllers\FavoriteController::class)->only('index', 'store', 'destroy');
    Route::apiResource('products', App\Http\Controllers\ProductController::class)->except('update', 'show');
    Route::post('/user/change-district', [AuthController::class, 'changeDistrict']);
    Route::post('/logout', [AuthController::class, 'logout']);
});




