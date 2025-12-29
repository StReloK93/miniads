<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{

   private function createToken(User $user): string
   {
      return $user->createToken('userToken', ['almighty'])->plainTextToken;
   }

   public function logout($request): void
   {
      $request->user()->currentAccessToken()->delete();
   }

   public function telegramAuth($request)
   {
      $telegram_user = $request->telegram_user;
      $first_name = $telegram_user['first_name'];
      $last_name = $telegram_user['last_name'];

      $user = User::updateOrCreate(
         ['telegram_user_id' => $telegram_user['id']],
         ['name' => "$first_name $last_name"],
      );

      if (Auth::loginUsingId($user->id)) {
         $token = $this->createToken($user);
         return response()->json(['token' => $token, 'type' => 'Bearer'], 200);
      }

      return response()->json(['message' => 'Login failed'], 500);
   }

}