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

	public function telegramSignIn(Request $request)
	{
		return $this->authService->telegramAuth($request);
	}

	public function telegramWidgetAuth(Request $request)
	{
		return $this->authService->telegramWidgetAuth($request);
	}

	public function testAuth()
	{
		return $this->authService->testAuth();
	}

	public function changeDistrict(Request $request)
	{
		$user = $request->user();
		$user->active_district_id = $request->input('district_id');
		$user->save();

		return $user->load('activeDistrict');
	}
}
