<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TelegramAuth;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/telegram/sign-in', [AuthController::class, 'telegramSignIn'])->middleware(TelegramAuth::class);

Route::controller(App\Http\Controllers\CategoryController::class)->group(function () {
    Route::get('/categories/parents', 'parents');
    Route::delete('categories/{id}/force', 'forceDelete');
});
Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
