<?php

namespace App\Services;

use Illuminate\Http\Request;
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
      $username = $telegram_user['username'];

      $user = User::updateOrCreate(
         ['telegram_user_id' => $telegram_user['id']],
         ['name' => "$first_name $last_name", 'username' => "$username"],
      );

      if (Auth::loginUsingId($user->id)) {
         $token = $this->createToken($user);
         return response()->json(['token' => $token, 'type' => 'Bearer'], 200);
      }

      return response()->json(['message' => 'Login failed'], 500);
   }

   public function telegramWidgetAuth(Request $request)
   {
      $data = $request->all(); // Widgetdan kelgan barcha malumotlar

      // 1. Validatsiya (Hashni tekshirish)
      if (!$this->checkTelegramWidgetHash($data)) {
         return response()->json(['message' => 'Maʼlumotlar soxta!'], 403);
      }

      // 2. Auth_date eskirganini tekshirish (ixtiyoriy, masalan 24 soat)
      if (time() - $data['auth_date'] > 86400) {
         return response()->json(['message' => 'Login muddati oʻtgan'], 403);
      }

      // 3. Foydalanuvchini bazadan qidirish yoki yaratish
      $firstName = $data['first_name'] ?? '';
      $lastName = $data['last_name'] ?? '';

      $user = User::updateOrCreate(
         ['telegram_user_id' => $data['id']],
         [
            'name' => trim("$firstName $lastName"),
            // Agar bazangizda username bo'lsa:
            // 'username' => $data['username'] ?? null 
         ],
      );

      // 4. Token generatsiya qilish (Sanctum)
      if ($user) {
         $token = $this->createToken($user);
         return response()->json(['token' => $token, 'type' => 'Bearer'], 200);
      }

      return response()->json(['message' => 'Login failed'], 500);
   }


   private function checkTelegramWidgetHash($data)
   {
      if (!isset($data['hash']))
         return false;

      $check_hash = $data['hash'];
      unset($data['hash']);

      $data_check_arr = [];
      foreach ($data as $key => $value) {
         $data_check_arr[] = $key . '=' . $value;
      }

      // Alifbo tartibida saralash shart!
      sort($data_check_arr);
      $data_check_string = implode("\n", $data_check_arr);

      $bot_token = env('TELEGRAM_BOT_TOKEN'); // .env dagi bot token
      $secret_key = hash('sha256', $bot_token, true);
      $hash = hash_hmac('sha256', $data_check_string, $secret_key);

      return hash_equals($hash, $check_hash);
   }
}