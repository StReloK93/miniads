<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
class Category extends Model
{
    use SoftDeletes, HasTranslations;

    protected array $translatable = ['name'];
    protected $fillable = [
        'name',
        'image',
        'parent_id',
        'is_page',
        'with_price',
        'with_image',
        'with_title',
        'listing_duration_days',
        'is_permanent',
        'is_featured',
        'featured_order',
        'translations',
    ];

    protected $casts = [
        'is_page' => 'boolean',
        'with_price' => 'boolean',
        'with_image' => 'boolean',
        'with_title' => 'boolean',
        'listing_duration_days' => 'integer',
        'is_permanent' => 'boolean',
        'is_featured' => 'boolean',
        'featured_order' => 'integer',
    ];

    /**
     * Sarlavha so'ralmaydigan kategoriya (with_title = false) uchun sarlavha: parametr qiymatlari tartib bo'yicha.
     * Masalan Naqd-Plastik: "Naqd → Plastik · 1 000 000 so'm". Qiymat bo'lmasa — kategoriya nomi.
     *
     * @param array<int|string, mixed> $values parameter_id → qiymat (asl, o'zbekcha)
     */
    public function autoTitle(array $values): string
    {
        $parts = $this->parameters()
            ->orderBy('category_parameters.sort_order')
            ->get()
            ->map(function (Parameter $parameter) use ($values) {
                $value = trim((string) ($values[$parameter->id] ?? ''));
                if ($value === '') {
                    return null;
                }
                if ($parameter->type === 'number' && is_numeric($value)) {
                    $value = number_format((float) $value, 0, '.', ' ');
                }

                return trim($value . ' ' . ($parameter->getRawOriginal('unit') ?? ''));
            })
            ->filter()
            ->values();

        return mb_substr($parts->isEmpty() ? $this->getRawOriginal('name') : $parts->implode(' · '), 0, 255);
    }

    /** Muddati ko'rsatilmagan kategoriyalarda e'lon shuncha kun turadi */
    public const DEFAULT_LISTING_DAYS = 30;

    /**
     * Shu kategoriyada hozir joylangan (yoki qayta faollashtirilgan) e'lon qachongacha turadi.
     * Muddatsiz kategoriyada — null (e'lon hech qachon o'chmaydi, Product::isPermanent).
     */
    public function listingExpiresAt(): ?Carbon
    {
        if ($this->is_permanent) {
            return null;
        }

        return now()->addDays($this->listing_duration_days ?: self::DEFAULT_LISTING_DAYS);
    }
    protected static function booted(): void
    {
        // Kategoriya o'zgarsa unga bog'liq keshlarni yangilaymiz
        $flush = function (): void {
            Cache::forever('categories:version', (int) Cache::get('categories:version', 0) + 1);
            Cache::forget('leaf_categories_list');
            Cache::forget(\App\Services\Bot\AdAiParserService::CATEGORIES_CONTEXT_CACHE);
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
        return $this->hasMany(Category::class, 'parent_id')->with('children')->select('id', 'name', 'translations', 'image', 'parent_id', 'is_page', 'listing_duration_days', 'is_permanent', 'is_featured');
    }

    public function parent()
    {
        // Bu asosiy kategoriyani olish uchun
        return $this->belongsTo(Category::class, 'parent_id')->with('parent')->select('id', 'name', 'translations', 'image', 'parent_id', 'is_page');
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
