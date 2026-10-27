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

	public function testAuth(Request $request)
	{
		return $this->authService->testAuth($request);
	}

	public function changeDistrict(Request $request)
	{
		$request->validate([
			'district_id' => ['nullable', 'integer', function ($attribute, $value, $fail) {
				// 0 = "Barcha shaharlar"
				if ((int) $value !== 0 && !\App\Models\District::whereKey($value)->exists()) {
					$fail(__('messages.city_not_found'));
				}
			}],
		]);

		$user = $request->user();
		$user->active_district_id = $request->district_id != 0 ? $request->input('district_id') : null;
		$user->save();

		return $user->load('activeDistrict');
	}

	public function logout(Request $request)
	{
		$this->authService->logout($request);

		return response()->json(['message' => __('messages.logged_out')]);
	}
}
