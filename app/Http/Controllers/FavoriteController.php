<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use App\Models\Product;
class FavoriteController extends Controller
{

    public function index(Request $request)
    {

        return Product::query()
            ->whereHas(
                'favorites',
                fn($q) =>
                $q->where('user_id', $request->user()->id)
            )
            ->active()
            ->withExists([
                'favorites as is_favorite' => fn($q) =>
                    $q->where('user_id', $request->user()->id)
            ])
            ->latest()
            ->get();

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        // Ikki marta bosilsa unique index xatosi chiqmasligi uchun
        return Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ]);
    }


    public function destroy($id, Request $request)
    {
        Favorite::where([
            ['user_id', '=', $request->user()->id],
            ['product_id', '=', $id]
        ])->delete();
        return response()->json(null, 204);
    }
}
