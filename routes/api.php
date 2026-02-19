<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TelegramAuth;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/telegram/sign-in', [AuthController::class, 'telegramSignIn'])->middleware(TelegramAuth::class);
Route::post('/telegram/widget-sign-in', [AuthController::class, 'telegramWidgetAuth']);


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
Route::apiResource('products', App\Http\Controllers\ProductController::class);
Route::apiResource('price-types', App\Http\Controllers\PriceTypeController::class);


