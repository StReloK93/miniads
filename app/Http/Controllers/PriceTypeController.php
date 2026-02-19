<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PriceType;
class PriceTypeController extends Controller
{

    public function index()
    {
        return PriceType::all();
    }

    public function show($id)
    {
        return PriceType::findOrFail($id);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'position' => 'required|string',
        ]);

        return PriceType::create($validated);
    }

    public function update(Request $request, $id)
    {
        $parameter = PriceType::findOrFail($id);
        $parameter->update($request->all());
        return response()->json($parameter);
    }

    public function destroy($id)
    {
        $parameter = PriceType::findOrFail($id);
        $parameter->delete();
        return response()->json(null, 204);
    }
}
