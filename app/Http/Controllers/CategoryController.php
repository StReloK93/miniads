<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
class CategoryController extends Controller
{
    //
    public function index()
    {
        return Category::without('children')->select('id', 'name')->get();
    }

    public function show($id)
    {
        return Category::without('children')->select('id', 'name', 'image')->findOrFail($id);
    }
    public function parents()
    {
        return Category::whereNull('parent_id')->select('id', 'name', 'image')->get();
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string',
            'image' => 'required_without:parent_id|mimes:svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Fayl nomini xavfsiz qilish (Original nomda keraksiz belgilar bo'lishi mumkin)
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // public/icons papkasiga yuklash
            $file->move(public_path('icons'), $fileName);

            // Validatsiya qilingan ma'lumotlarga yo'lni qo'shamiz
            $validated['image'] = '/icons/' . $fileName;
        }

        $category = Category::create($validated);
        return response()->json($category, 201);
    }


    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'sometimes|mimes:jpeg,png,jpg,svg',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('icons'), $fileName);
            $path = '/icons/' . $fileName;
            $request->merge(['image' => $path]);
        }

        $category->update($request->all());
        return response()->json($category, 200);
    }
}
