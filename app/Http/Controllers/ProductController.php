<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductParameterValue;
class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }

    public function store(Request $request)
    {

        $product = Product::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'user_id' => auth()->id(), // yoki $request->user_id
        ]);

        // 2. Dinamik parametrlarni saqlash
        if ($request->has('parameters')) {
            $params = $request->extra_parameters; // [ "1" => 3, "2" => 55 ]

            foreach ($params as $paramId => $value) {
                // Faqat qiymat kiritilgan bo'lsa saqlaymiz
                if ($value !== null) {
                    ProductParameterValue::create([
                        'pruduct_id' => $product->id,
                        'parameter_id' => $paramId,
                        'value' => $value,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'E\'lon muvaffaqiyatli joylandi!'], 201);
    }
}
