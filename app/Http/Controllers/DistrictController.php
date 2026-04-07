<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\District;
class DistrictController extends Controller
{

    public function index()
    {
        return District::all();
    }

}
