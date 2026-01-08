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



        $parameter = Parameter::create($request->all());

        if (request()->has('options')) {
            $options = request()->input('options');
            $insertedOptions = [];
            foreach ($options as $option) {
                $insertedOptions[] = [
                    'value' => $option,
                    'parameter_id' => $parameter->id,
                ];
            }

            ParameterOption::insert($insertedOptions);
        }
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
