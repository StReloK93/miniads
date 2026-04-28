<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TelegramAuth;


Route::get('/user', function (Request $request) {
    return $request->user()->load('activeDistrict');
})->middleware('auth:sanctum');

Route::post('/telegram/sign-in', [AuthController::class, 'telegramSignIn'])->middleware(TelegramAuth::class);
Route::post('/telegram/widget-sign-in', [AuthController::class, 'telegramWidgetAuth']);

if (app()->environment('local')) {
    Route::post('/test-auth', [AuthController::class, 'testAuth']);
}


Route::controller(App\Http\Controllers\CategoryController::class)->group(function () {
    Route::get('/categories/parents/{id?}', 'parents');
    Route::post('/categories/change_parent/{id}', 'changeParent');

    Route::delete('categories/{id}/force', 'forceDelete');
    Route::post('categories/{id}', 'update');
    Route::get('categories/{id}/products', 'products');
});

Route::apiResource('categories', App\Http\Controllers\CategoryController::class)->except('update');
Route::apiResource('parameters', App\Http\Controllers\ParameterController::class);
Route::apiResource('categories.parameters', App\Http\Controllers\CategoryParameterController::class);


Route::apiResource('districts', App\Http\Controllers\DistrictController::class);

Route::apiResource('price-types', App\Http\Controllers\PriceTypeController::class);



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






