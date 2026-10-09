<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TelegramAuth;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

Route::get('/user', function (Request $request) {
    return $request->user()->load('activeDistrict');
})->middleware('auth:sanctum');




Route::get('/sendmessage', function (Request $request) {
    $introText = "Debil";

    return Telegraph::chat('1016647977')
        ->html($introText)
        // ->keyboard(
        //     Keyboard::make()->buttons([
        //         Button::make("🚀 E'lon joylash")
        //             ->url('https://t.me/NavoiyElonBot?startapp'),
        //     ])
        // )
        ->send();
});

Route::post('/telegram/sign-in', [AuthController::class, 'telegramSignIn'])->middleware(TelegramAuth::class);
Route::post('/telegram/widget-sign-in', [AuthController::class, 'telegramWidgetAuth']);


Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegraph.webhook');

if (app()->environment('local')) {
    Route::post('/test-auth', [AuthController::class, 'testAuth']);
}


Route::controller(App\Http\Controllers\CategoryController::class)->group(function () {
    Route::get('/categories/parents/{id?}', 'parents');
    Route::get('categories/{id}/products', 'products');
});
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
        Route::get('users', 'users');
        Route::patch('users/{id}/role', 'updateUserRole');
        Route::get('bot-settings', 'getBotSettings');
        Route::post('bot-settings', 'updateBotSettings');
        Route::post('bot-settings/run-now', 'runBotImport');
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

    Route::get('/products/custom/latest_ten', [App\Http\Controllers\ProductController::class, 'latestTen']);
    Route::get('/products/custom/my_ads/{status}', [App\Http\Controllers\ProductController::class, 'myAds']);
    Route::get('/products/custom/search', [App\Http\Controllers\ProductController::class, 'search']);
    Route::post('/products/{id}/deactivate', [App\Http\Controllers\ProductController::class, 'deactivate']);
    Route::post('/products/{id}/activate', [App\Http\Controllers\ProductController::class, 'activate']);
    Route::post('/products/{id}', [App\Http\Controllers\ProductController::class, 'update']);
    Route::get('/products/{id}/edit', [App\Http\Controllers\ProductController::class, 'edit']);


    Route::apiResource('favorites', App\Http\Controllers\FavoriteController::class)->only('index', 'store', 'destroy');
    Route::apiResource('products', App\Http\Controllers\ProductController::class)->except('update');
    Route::post('/user/change-district', [AuthController::class, 'changeDistrict']);
});




