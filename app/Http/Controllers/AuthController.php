<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
class AuthController extends Controller
{
	public function adminLogin(Request $request)
	{
		$credentials = $request->only('login', 'password');

		if (Auth::attempt($credentials)) {
			$user = Auth::user();
			if ($user->role !== 'admin') {
				return response()->json(['error' => 'Siz admin emassiz'], 403);
			}
			return response()->json([
				'token' => $user->createToken('admin_token')->plainTextToken
			]);
		}
	}
}
