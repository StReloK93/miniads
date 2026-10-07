<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;
use App\Models\District;
class DistrictController extends Controller
{

    public function index()
    {
        return Cache::remember('districts:all', now()->addMinutes(15), function () {
            $districts = District::query()
                ->orderBy('name')
                ->get();

            $districts->prepend((object) [
                'id' => 0,
                'name' => 'Barcha shaharlar',
            ]);

            return $districts->values();
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:districts,name'],
        ]);

        $district = District::create($validated);
        Cache::forget('districts:all');

        return response()->json($district, 201);
    }

    public function update(Request $request, int $id)
    {
        $district = District::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:districts,name,'.$district->id],
        ]);

        $district->update($validated);
        Cache::forget('districts:all');

        return response()->json($district);
    }

    public function destroy(int $id)
    {
        $district = District::findOrFail($id);
        $district->delete();
        Cache::forget('districts:all');

        return response()->json(null, 204);
    }
}
