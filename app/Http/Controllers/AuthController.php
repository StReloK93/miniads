<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Services\AuthService;
class AuthController extends Controller
{

	private AuthService $authService;

	public function __construct(AuthService $authService)
	{
		$this->authService = $authService;
	}
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

	public function telegramSignIn(Request $request)
	{
		return $this->authService->telegramAuth($request);
	}
}
