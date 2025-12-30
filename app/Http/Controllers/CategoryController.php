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
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('icons'), $fileName);
            $path = '/icons/' . $fileName;
            $request->merge(['image' => $path]);
        }

        $category = Category::create($request->all());
        return response()->json($category, 201);
    }

}
