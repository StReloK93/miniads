<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
class CategoryParameterController extends Controller
{
    //

    public function store(Request $request, Category $category)
    {
        $parameter = [];
        foreach ($request->all() as $value) {
            $parameter[] = $category->parameters()->create($value);
        }

        return response()->json($parameter, 201);
    }

    public function index(Category $category)
    {
        // Faqat shu kategoriyaga tegishli parametrlarni olish
        return response()->json($category->parameters);
    }
}
