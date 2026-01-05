<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
Route::get('/{any}', function () {

    $categories = Category::whereNull('parent_id')->select('image')->get();
    return view('app', compact('categories'));
})->where('any', '.*');