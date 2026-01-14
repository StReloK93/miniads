<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;
use App\Models\ParameterOption;
class ParameterController extends Controller
{
    //

    public function index()
    {
        //

        $parameters = Parameter::all();
        return response()->json($parameters);
    }

    public function show($id)
    {
        //

        $parameter = Parameter::findOrFail($id);
        return response()->json($parameter);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'placeholder' => 'required|string',
            'type' => 'required|string',
            'component' => 'required|string',
            'unit' => 'nullable|string',
            'options' => 'nullable|array', // Options massiv bo'lishi shart
        ]);

        $parameter = Parameter::create($validated);


        return response()->json($parameter->fresh(), 201);
    }

    public function update(Request $request, $id)
    {
        $parameter = Parameter::findOrFail($id);
        $parameter->update($request->all());
        return response()->json($parameter);
    }

    public function destroy($id)
    {
        $parameter = Parameter::findOrFail($id);
        $parameter->delete();
        return response()->json(null, 204);
    }
}
