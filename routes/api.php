<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TelegramAuth;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index']);

Route::post('/telegram/sign-in', [AuthController::class, 'telegramSignIn'])->middleware(TelegramAuth::class);

Route::get('/categories/parents', [App\Http\Controllers\CategoryController::class, 'parents']);
Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
