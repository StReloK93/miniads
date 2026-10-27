<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;
use App\Models\District;
class DistrictController extends Controller
{

    public function index()
    {
        $districts = Cache::remember('districts:list', now()->addMinutes(15), fn () => District::query()
            ->orderBy('name')
            ->get());

        // "Barcha shaharlar" — keshdan tashqarida: so'rovchi tilida
        return $districts->toBase()->prepend((object) [
            'id' => 0,
            'name' => __('messages.all_cities'),
        ])->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:districts,name'],
            ...District::translationRules(),
        ]);

        $district = new District($validated);
        $district->fillTranslations($request->all())->save();
        Cache::forget('districts:list');

        return response()->json($district, 201);
    }

    public function update(Request $request, int $id)
    {
        $district = District::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:districts,name,'.$district->id],
            ...District::translationRules(),
        ]);

        $district->fill($validated)->fillTranslations($request->all())->save();
        Cache::forget('districts:list');

        return response()->json($district);
    }

    public function destroy(int $id)
    {
        $district = District::findOrFail($id);
        $district->delete();
        Cache::forget('districts:list');

        return response()->json(null, 204);
    }
}
