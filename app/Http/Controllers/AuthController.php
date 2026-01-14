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
}
