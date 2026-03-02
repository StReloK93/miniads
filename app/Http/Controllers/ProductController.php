<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductParameterValue;
use Auth;
class ProductController extends Controller
{
    public function index()
    {
        return Product::withExists([
            'favorites as is_favorite' => fn($q) =>
                $q->where('user_id', auth()->id())
        ])->all();
    }

    public function store(Request $request)
    {
        $product = Product::create([
            'title' => $request->title,
            'price' => $request->price,
            'price_type_id' => $request->price_type_id,
            'description' => $request->description,
            'phone' => $request->phone,
            'category_id' => $request->category_id,
            'user_id' => 1, // yoki $request->user_id
            'district' => 2, // yoki $request->user_id
        ]);


        // 2. Dinamik parametrlarni saqlash
        if ($request->has('parameters')) {
            $params = $request->parameters;

            foreach ($params as $param) {
                if ($param['id'] !== null) {
                    ProductParameterValue::create([
                        'product_id' => $product->id,
                        'parameter_id' => $param['id'],
                        'value' => $param['value'],
                    ]);
                }
            }
        }


        // 3. Rasmlarni saqlash (File Upload)
        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image['file']->store('products', 'public');

                // ProductImage modeliga saqlash
                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'src' => $path // Bu bazada 'products/filename.jpg' ko'rinishida saqlanadi
                ]);
            }
        }



        return response()->json(['message' => 'E\'lon muvaffaqiyatli joylandi!'], 201);
    }


    public function show($id)
    {
        $product = Product::withExists([
            'favorites as is_favorite' => fn($q) =>
                $q->where('user_id', auth()->id())
        ])->findOrFail($id);
        return response()->json($product);
    }


    public function latestTen()
    {
        return Product::with('category')->withExists([
            'favorites as is_favorite' => fn($q) =>
                $q->where('user_id', auth()->id())
        ])->latest()->take(10)->get();
    }

}
