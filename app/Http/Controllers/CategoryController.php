<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
class CategoryController extends Controller
{
    //
    public function index()
    {
        return Category::whereNull('parent_id')->get();
    }


    public function store(Request $request)
    {
        $category = Category::create($request->all());
        return response()->json($category, 201);
    }

}
