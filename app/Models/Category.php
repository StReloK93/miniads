<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
class Category extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'image',
        'parent_id',
        'is_page',
        'with_price',
        'with_image',
        'listing_duration_days',
    ];

    protected $casts = [
        'is_page' => 'boolean',
        'with_price' => 'boolean',
        'with_image' => 'boolean',
        'listing_duration_days' => 'integer',
    ];
    protected static function booted(): void
    {
        // Kategoriya o'zgarsa unga bog'liq keshlarni yangilaymiz
        $flush = function (): void {
            Cache::forever('categories:version', (int) Cache::get('categories:version', 0) + 1);
            Cache::forget('leaf_categories_list');
            Cache::forget('leaf_categories_with_parents');
            // Uzoq ishlaydigan jarayonlarda (queue worker) ham yangi daraxt olinsin
            self::$rootIds = null;
        };

        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get('categories:version', 0);
    }

    // Asosiy bo'lim (Uy-joy, Avto...) — e'lon kartasida ikonka va rang shundan olinadi
    protected $appends = ['root_id'];

    private static ?array $rootIds = null;

    /** @return array<int, int> kategoriya id → eng yuqori ota-ona id */
    public static function rootIdMap(): array
    {
        return self::$rootIds ??= Cache::remember(
            'categories:v' . self::cacheVersion() . ':root-ids',
            now()->addDay(),
            function (): array {
                $parents = self::withTrashed()->pluck('parent_id', 'id')->all();
                $roots = [];
                foreach (array_keys($parents) as $id) {
                    $root = $id;
                    // Sikl himoyasi: daraxt chuqurligi 10 dan oshmaydi
                    for ($depth = 0; $depth < 10 && !empty($parents[$root]); $depth++) {
                        $root = $parents[$root];
                    }
                    $roots[$id] = $root;
                }

                return $roots;
            },
        );
    }

    public function getRootIdAttribute(): ?int
    {
        return $this->id ? (self::rootIdMap()[$this->id] ?? $this->id) : null;
    }

    public function children()
    {
        // Bu podkategoriyalarni olish uchun
        return $this->hasMany(Category::class, 'parent_id')->with('children')->select('id', 'name', 'image', 'parent_id', 'is_page', 'listing_duration_days');
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
