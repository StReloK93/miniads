<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;
use App\Models\District;
class DistrictController extends Controller
{

    public function index()
    {
        return Cache::rememberForever('districts:all', function () {
            $districts = District::query()
                ->orderBy('name')
                ->get();

            $districts->prepend(
                new District([
                    'id' => 0,
                    'name' => 'Barchasi',
                ])
            );

            return $districts->values();
        });
    }
}
