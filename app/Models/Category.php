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

    public function parameters()
    {
        // hasMany o'rniga belongsToMany ishlatamiz
        return $this->belongsToMany(Parameter::class, 'category_parameters')
            ->using(CategoryParameter::class)
            ->withPivot('is_required', 'sort_order');
    }
}
