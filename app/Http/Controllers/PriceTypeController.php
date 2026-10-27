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
            ...PriceType::translationRules(),
        ]);

        $priceType = new PriceType($validated);
        $priceType->fillTranslations($request->all())->save();

        return $priceType;
    }

    public function update(Request $request, $id)
    {
        $parameter = PriceType::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:40'],
            'position' => ['required', 'in:right,left'],
            ...PriceType::translationRules(),
        ]);
        $parameter->fill($validated)->fillTranslations($request->all())->save();
        return response()->json($parameter);
    }

    public function destroy($id)
    {
        $parameter = PriceType::findOrFail($id);
        $parameter->delete();
        return response()->json(null, 204);
    }
}
