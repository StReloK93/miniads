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
            ->withExists([
                'favorites as is_favorite' => fn($q) =>
                    $q->where('user_id', $request->user()->id)
            ])
            ->latest()
            ->get();

    }

    public function store(Request $request)
    {
        return Favorite::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
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
