<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BackColor;
class BackColorController extends Controller
{
    public function index()
    {
        return BackColor::all();
    }
}
