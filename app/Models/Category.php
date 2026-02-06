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
        'is_page',
    ];

    protected $casts = [
        'is_page' => 'boolean',
    ];
    public function children()
    {
        // Bu podkategoriyalarni olish uchun
        return $this->hasMany(Category::class, 'parent_id')->with('children')->select('id', 'name', 'image', 'parent_id', 'is_page');
    }

    public function parent()
    {
        // Bu asosiy kategoriyani olish uchun
        return $this->belongsTo(Category::class, 'parent_id')->with('parent')->select('id', 'name', 'image', 'parent_id', 'is_page');
    }

    public function parameters()
    {
        // hasMany o'rniga belongsToMany ishlatamiz
        return $this->belongsToMany(Parameter::class, 'category_parameters')
            ->using(CategoryParameter::class)
            ->withPivot('is_required', 'sort_order');
    }


    public function products()
    {
        return $this->hasMany(Product::class);
    }


    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            $breadcrumbs[] = [
                'id' => $current->id,
                'name' => $current->name,
            ];

            $current = $current->parent;
        }

        return array_reverse($breadcrumbs);
    }
}
