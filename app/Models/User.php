<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'username',
        'name',
        'telegram_user_id',
        'active_district_id',
        'role',
    ];


    public function activeDistrict()
    {
        return $this->belongsTo(District::class, 'active_district_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public static function getBotUser(): self
    {
        $botUser = self::where('username', 'telegramAssistent')
            ->orWhere('username', 'telegram_bot')
            ->first();

        if ($botUser) {
            if ($botUser->username !== 'telegramAssistent' || $botUser->name !== 'Telegram Assistent') {
                $botUser->update([
                    'username' => 'telegramAssistent',
                    'name' => 'Telegram Assistent',
                ]);
            }
            return $botUser;
        }

        return self::create([
            'username' => 'telegramAssistent',
            'name' => 'Telegram Assistent',
            'telegram_user_id' => 999999999,
            'role' => 'user',
            'active_district_id' => null,
        ]);
    }
}
