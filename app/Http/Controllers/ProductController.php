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
        return Product::all();
    }

    public function store(Request $request)
    {
        $product = Product::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'user_id' => 1, // yoki $request->user_id
            'district' => 2, // yoki $request->user_id
        ]);


        // 2. Dinamik parametrlarni saqlash
        if ($request->has('parameters')) {
            $params = $request->parameters; // [ "1" => 3, "2" => 55 ]

            foreach ($params as $param) {
                // Faqat qiymat kiritilgan bo'lsa saqlaymiz
                if ($param['value'] !== null) {
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
                // Rasmni 'public/products' papkasiga saqlaymiz
                // store() funksiyasi avtomatik ravishda noyob nom beradi
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
        $product = Product::findOrFail($id);
        return response()->json($product);
    }


}
