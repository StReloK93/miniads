<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Category extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'image',
        'parent_id',
    ];

    protected $with = ['children'];
    public function children()
    {
        // Bu podkategoriyalarni olish uchun
        return $this->hasMany(Category::class, 'parent_id')->select('id', 'name', 'image', 'parent_id');
    }

    public function parent()
    {
        // Bu asosiy kategoriyani olish uchun
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
