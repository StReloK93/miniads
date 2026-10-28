<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class AuthService
{


   public function logout($request): void
   {
      $request->user()->currentAccessToken()->delete();
   }

   public function telegramAuth($request)
   {
      $telegram_user = $request->telegram_user;
      // Telegramda familiya (va ba'zan username) bo'lmasligi mumkin
      $first_name = $telegram_user['first_name'] ?? '';
      $last_name = $telegram_user['last_name'] ?? '';
      $username = $telegram_user['username'] ?? null;

      $user = User::updateOrCreate(
         ['telegram_user_id' => $telegram_user['id']],
         ['name' => trim("$first_name $last_name"), 'username' => $username],
      );

      // initData: foydalanuvchi botga yozishga ruxsat bergan (yoki botni ishga tushirgan). false — o'zgartirmaymiz:
      // bu maydon hamma klientda kelavermaydi
      if (!empty($telegram_user['allows_write_to_pm']) && !$user->bot_can_message) {
         $user->forceFill(['bot_can_message' => true])->save();
      }

      if (Auth::loginUsingId($user->id)) {
         $this->pruneTokens($user);

         $token = $user->createToken('mini-app')->plainTextToken;


         return response()->json(['token' => $token, 'type' => 'Bearer', 'user' => $user->load('activeDistrict')], 200);
      }

      return response()->json(['message' => 'Login failed'], 500);
   }

   /**
    * Ishlatilayotgan sessiya hech qachon o'chirilmaydi: aks holda boshqa qurilma/oynada (telefon + kompyuter,
    * kanaldagi e'lon havolasidan qayta ochilish) foydalanuvchi ilovadan chiqib ketadi.
    * Faqat uzoq vaqt ishlatilmagan, tashlab ketilgan tokenlar tozalanadi.
    */
   private function pruneTokens(User $user, int $unusedDays = 30): void
   {
      $user->tokens()
         ->where(fn ($q) => $q->where('last_used_at', '<', now()->subDays($unusedDays))
            ->orWhere(fn ($q) => $q->whereNull('last_used_at')->where('created_at', '<', now()->subDays($unusedDays))))
         ->delete();
   }


   // 
   // 
   // 
   // 
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
            'username' => $data['username'] ?? null
         ],
      );

      // 4. Token generatsiya qilish (Sanctum)
      if ($user) {
         $this->pruneTokens($user);

         $token = $user->createToken('mini-app')->plainTextToken;
         return response()->json(['token' => $token, 'type' => 'Bearer', 'user' => $user], 200);
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

      $bot_token = (string) config('services.telegram.bot_token'); // .env dagi bot token
      $secret_key = hash('sha256', $bot_token, true);
      $hash = hash_hmac('sha256', $data_check_string, $secret_key);

      return hash_equals($hash, $check_hash);
   }






   public function testAuth(Request $request)
   {
      if (
         !app()->environment('local')
         || !in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true)
         || !in_array($request->ip(), ['127.0.0.1', '::1'], true)
      ) {
         abort(403, 'Forbidden');
      }

      $user = User::firstOrCreate(
         ['username' => 'local-dev'],
         ['name' => 'Local Developer', 'role' => 'admin'],
      );

      if (Auth::loginUsingId($user->id)) {
         $user->tokens()->delete();

         $token = $user->createToken('mini-app')->plainTextToken;


         return response()->json(['token' => $token, 'type' => 'Bearer', 'user' => $user], 200);
      }

      return response()->json(['message' => 'Login failed'], 500);
   }
}