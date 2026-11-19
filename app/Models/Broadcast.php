<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/** Bot obunachilariga yuborilgan post va uning yetkazilish hisobi */
class Broadcast extends Model
{
    protected $fillable = [
        'created_by',
        'text',
        'html',
        'photo_path',
        'photo_file_id',
        'button_text',
        'button_url',
        'total',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];

    protected $hidden = ['photo_path', 'photo_file_id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Hammasiga urinib bo'lingach: tugadi deb belgilanadi va vaqtinchalik rasm o'chiriladi */
    public function finishIfDone(): void
    {
        $this->refresh();
        if ($this->finished_at || $this->sent + $this->failed < $this->total) {
            return;
        }

        if ($this->photo_path) {
            File::delete($this->photo_path);
        }
        $this->forceFill(['finished_at' => now(), 'photo_path' => null])->save();
    }
}
