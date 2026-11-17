<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vitrina;
use App\Models\VitrinaItem;
use App\Models\VitrinaSection;
use App\Services\TelegramNotifier;
use App\Support\Phone;
use App\Support\UzbekText;
use App\Support\ImageCrop;
use App\Support\VitrinaImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Vitrinalar: ommaviy katalog va sahifa, egasi uchun tahrirlash (o'z vitrinasi, bo'limlar, mahsulotlar),
 * admin uchun tasdiqlash. Bir foydalanuvchi — bitta vitrina.
 */
class VitrinaController extends Controller
{
    /* ================= Ommaviy ================= */

    /** Bosh sahifadagi qatorda nechta vitrina (qolgani — katalogda) */
    public const STRIP_LIMIT = 12;

    /**
     * Tasdiqlangan vitrinalar; city_id — shu shahar va butun viloyat vitrinalari.
     *   katalog: tasdiq belgisi borlar, keyin yangi qo'shilganlar (tahrirlab yuqoriga chiqib bo'lmaydi);
     *   strip=1 (bosh sahifa): 12 ta, kunlik aralashtirilgan — hamma navbat bilan boshga chiqadi,
     *   bir kun ichida tartib o'zgarmaydi (sahifa yangilansa qator "sakramaydi").
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => ['nullable', Rule::in(Vitrina::TYPES)],
            'city_id' => ['nullable', 'integer'],
            'strip' => ['nullable', 'boolean'],
        ]);

        $query = Vitrina::approved()
            ->when($request->integer('city_id'), fn ($q, int $cityId) => $q->where(fn ($q) => $q
                ->where('district_id', $cityId)
                ->orWhereNull('district_id')))
            ->when($request->input('type'), fn ($q, string $type) => $q->where('type', $type));
        $columns = ['id', 'name', 'slug', 'type', 'logo', 'cover', 'description', 'district_id', 'is_verified', 'updated_at'];

        if ($request->boolean('strip')) {
            // Bazaga bog'liq bo'lmagan kunlik aralashtirish: id + sana xeshi (tasdiqlanganlar baribir oldinda)
            $day = now()->toDateString();
            $ids = (clone $query)->toBase()->get(['id', 'is_verified'])
                ->sortBy(fn ($row) => [$row->is_verified ? 0 : 1, crc32("{$day}:{$row->id}")])
                ->take(self::STRIP_LIMIT)
                ->pluck('id')
                ->all();

            return Vitrina::whereIn('id', $ids)->get($columns)
                ->sortBy(fn (Vitrina $vitrina) => array_search($vitrina->id, $ids, true))
                ->values();
        }

        return $query
            ->withCount('items')
            ->orderByDesc('is_verified')
            ->latest('id')
            ->limit(100)
            ->get($columns);
    }

    /** Vitrina sahifasi (slug yoki id). Tasdiqlanmagani faqat egasi va admin uchun ochiladi */
    public function show(Request $request, string $slug)
    {
        $vitrina = Vitrina::with(['sections', 'items'])
            ->withCount('items')
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id', ctype_digit($slug) ? (int) $slug : 0))
            ->first();

        $user = $request->user();
        $isOwner = $vitrina && $user && $user->id === $vitrina->user_id;
        $canSee = $vitrina && ($vitrina->status === Vitrina::APPROVED || $isOwner || $user?->role === 'admin');
        abort_unless($canSee, 404, __('messages.vitrina_not_found'));

        if (!$isOwner) {
            // Hodisasiz: updated_at va kesh o'zgarmasin
            Vitrina::whereKey($vitrina->id)->increment('views_count');
        }

        $vitrina->setAttribute('is_owner', $isOwner);

        return $vitrina;
    }

    /**
     * Qidiruvdagi "Vitrinalardan" bloki: so'rovdagi barcha so'zlar mahsulot nomi, tavsifi, bo'limi yoki
     * vitrina nomida uchrasin ("divan" → do'konlardagi divanlar). Faqat tasdiqlangan vitrina va hozir bor mahsulot.
     * city_id — e'lonlardagidek: shu shahar va butun viloyat vitrinalari.
     */
    public function searchItems(Request $request)
    {
        $request->validate([
            'search' => ['required', 'string', 'max:100'],
            'city_id' => ['nullable', 'integer'],
        ]);

        $terms = array_values(array_filter(
            preg_split('/\s+/u', UzbekText::normalize((string) $request->input('search')), -1, PREG_SPLIT_NO_EMPTY),
            fn (string $term) => mb_strlen($term) >= 2,
        ));
        if (!$terms) {
            return [];
        }

        // Nomzodlar: faqat matn ustunlari (vitrina mahsulotlari e'lonlardan ancha kam)
        $candidates = VitrinaItem::query()
            ->setEagerLoads([])
            ->join('vitrinas', 'vitrinas.id', '=', 'vitrina_items.vitrina_id')
            ->leftJoin('vitrina_sections', 'vitrina_sections.id', '=', 'vitrina_items.vitrina_section_id')
            ->where('vitrinas.status', Vitrina::APPROVED)
            ->where('vitrina_items.is_available', true)
            ->when($request->integer('city_id'), fn ($q, int $cityId) => $q->where(fn ($q) => $q
                ->where('vitrinas.district_id', $cityId)
                ->orWhereNull('vitrinas.district_id')))
            ->toBase()
            ->get([
                'vitrina_items.id', 'vitrina_items.title', 'vitrina_items.description',
                'vitrina_sections.name as section_name', 'vitrinas.name as vitrina_name', 'vitrinas.is_verified',
            ]);

        // Qisqa so'z ("osh") faqat o'zi va oddiy qo'shimchalari bilan: "oshi", "oshga" — lekin "oshxona" emas.
        // Vitrina nomi faqat uzun so'zlarda hisobga olinadi: "osh" deb qidirilsa "Osh Markazi"dagi kompot chiqmasin
        $patterns = array_map(fn (string $term) => [
            mb_strlen($term) <= 3
                ? '/(^| )' . preg_quote($term, '/') . '( |$|i |i$|ni|da|ga|dan|lar|ning)/u'
                : '/(^| )' . preg_quote($term, '/') . '/u',
            mb_strlen($term) >= 4,
        ], $terms);
        $scored = [];
        foreach ($candidates as $row) {
            $title = UzbekText::normalize($row->title);
            $rest = UzbekText::normalize("{$row->description} {$row->section_name}");
            $vitrinaName = UzbekText::normalize($row->vitrina_name);

            $score = 0;
            foreach ($patterns as [$pattern, $matchVitrinaName]) {
                $termScore = (preg_match($pattern, $title) ? 10 : 0)
                    + (preg_match($pattern, $rest) ? 3 : 0)
                    + ($matchVitrinaName && preg_match($pattern, $vitrinaName) ? 2 : 0);
                if ($termScore === 0) {
                    continue 2;
                }
                $score += $termScore;
            }
            $scored[$row->id] = $score + ($row->is_verified ? 1 : 0);
        }

        arsort($scored);
        $ids = array_slice(array_keys($scored), 0, self::SEARCH_LIMIT);

        return VitrinaItem::with('vitrina:id,name,slug,logo,type,is_verified')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (VitrinaItem $item) => array_search($item->id, $ids, true))
            ->values();
    }

    private const SEARCH_LIMIT = 12;

    /* ================= Egasi ================= */

    /** O'z vitrinam (bo'limlar va mahsulotlar bilan) yoki null */
    public function mine(Request $request)
    {
        // Obyekt ichida: json(null) "{}" bo'lib ketadi va frontend uni vitrina deb o'ylaydi
        return ['vitrina' => $request->user()->vitrina()->with(['sections', 'items'])->withCount('items')->first()];
    }

    /** Yaratish yoki yangilash (multipart: logo, cover — rasm; remove_logo / remove_cover — o'chirish) */
    public function save(Request $request)
    {
        $user = $request->user();
        abort_if($user->isBlocked(), 403, __('messages.blocked'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(Vitrina::TYPES)],
            'layout' => ['nullable', Rule::in(Vitrina::LAYOUTS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            'address' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'telegram' => ['nullable', 'string', 'max:40'],
            'instagram' => ['nullable', 'string', 'max:40'],
            'work_hours' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'max:8192'],
            'cover' => ['nullable', 'image', 'max:10240'],
        ]);

        $data['district_id'] = ($data['district_id'] ?? null) ?: null;
        $data['layout'] = ($data['layout'] ?? null) ?: null;
        $data['phone'] = Phone::format($data['phone'] ?? null);
        $data['telegram'] = $this->handle($data['telegram'] ?? null);
        $data['instagram'] = $this->handle($data['instagram'] ?? null);

        $vitrina = $user->vitrina()->first() ?? new Vitrina();
        $isNew = !$vitrina->exists;
        $vitrina->fill(collect($data)->except(['logo', 'cover'])->all());

        if ($isNew) {
            $vitrina->user_id = $user->id;
            $vitrina->slug = Vitrina::uniqueSlug($data['name']);
            // Admin o'z vitrinasini kutmaydi; boshqalar — tasdiqdan keyin ko'rinadi
            $vitrina->status = $user->role === 'admin' ? Vitrina::APPROVED : Vitrina::PENDING;
        } elseif ($vitrina->status === Vitrina::REJECTED) {
            // Rad etilgan vitrina tuzatilgach — qayta ko'rib chiqishga
            $vitrina->status = Vitrina::PENDING;
            $vitrina->reject_reason = null;
        }

        foreach (['logo' => VitrinaImage::LOGO, 'cover' => VitrinaImage::COVER] as $field => $size) {
            if ($request->hasFile($field)) {
                VitrinaImage::delete($vitrina->{$field});
                $vitrina->{$field} = VitrinaImage::store($request->file($field), $field, $size);
            } elseif ($request->boolean("remove_{$field}")) {
                VitrinaImage::delete($vitrina->{$field});
                $vitrina->{$field} = null;
            }
        }

        $vitrina->save();

        return response()->json($vitrina->load(['sections', 'items'])->loadCount('items'), $isNew ? 201 : 200);
    }

    public function storeSection(Request $request)
    {
        $vitrina = $this->ownVitrina($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:40']]);

        $section = $vitrina->sections()->create([
            'name' => $data['name'],
            'position' => (int) $vitrina->sections()->max('position') + 1,
        ]);

        return response()->json($section, 201);
    }

    public function updateSection(Request $request, int $id)
    {
        $section = $this->ownVitrina($request)->sections()->findOrFail($id);
        $section->update($request->validate(['name' => ['required', 'string', 'max:40']]));

        return $section;
    }

    /** Bo'lim o'chsa mahsulotlari o'chmaydi — "Boshqa" ga tushadi (section_id = null) */
    public function destroySection(Request $request, int $id)
    {
        $this->ownVitrina($request)->sections()->findOrFail($id)->delete();

        return response()->noContent();
    }

    public function storeItem(Request $request)
    {
        $vitrina = $this->ownVitrina($request);
        $item = new VitrinaItem(['position' => (int) $vitrina->items()->max('position') + 1]);
        $item->vitrina()->associate($vitrina);

        return response()->json($this->fillItem($request, $vitrina, $item), 201);
    }

    /** POST + multipart (PHP PUT'da fayllarni o'qimaydi) */
    public function updateItem(Request $request, int $id)
    {
        $vitrina = $this->ownVitrina($request);

        return $this->fillItem($request, $vitrina, $vitrina->items()->findOrFail($id));
    }

    /** Mahsulotlar tartibi: ids — yangi tartibdagi id'lar (begona id'lar e'tiborsiz qoladi) */
    public function orderItems(Request $request)
    {
        $vitrina = $this->ownVitrina($request);
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($vitrina, $data) {
            foreach (array_values(array_unique($data['ids'])) as $index => $id) {
                $vitrina->items()->whereKey($id)->update(['position' => $index + 1]);
            }
        });
        $vitrina->touch();

        return $vitrina->items()->get(['id', 'position']);
    }

    /**
     * Egasi uchun statistika: jami ko'rishlar, so'nggi 30 kunda qo'ng'iroq va Telegram bosishlar
     * (ilova track("contact_*", "v<id>") yozadi, activity:aggregate har 10 daqiqada yig'adi),
     * vitrinadan chiqarilgan e'lonlar va ularning ko'rishlari.
     */
    public function stats(Request $request)
    {
        $vitrina = $request->user()->vitrina()->first();
        abort_unless($vitrina, 404, __('messages.vitrina_not_found'));

        $days = 30;
        $contacts = DB::table('activity_stats')
            ->where('ref', "v{$vitrina->id}")
            ->whereIn('event', ['contact_phone', 'contact_telegram'])
            ->where('date', '>=', now()->subDays($days - 1)->toDateString())
            ->groupBy('event')
            ->selectRaw('event, SUM(hits) as hits')
            ->pluck('hits', 'event');

        $ads = Product::where('vitrina_id', $vitrina->id)->setEagerLoads([]);

        return [
            'days' => $days,
            'views' => (int) $vitrina->views_count,
            'phone' => (int) ($contacts['contact_phone'] ?? 0),
            'telegram' => (int) ($contacts['contact_telegram'] ?? 0),
            'ads' => (clone $ads)->count(),
            'ads_views' => (int) (clone $ads)->sum('views_count'),
        ];
    }

    public function destroyItem(Request $request, int $id)
    {
        $this->ownVitrina($request)->items()->findOrFail($id)->purge();

        return response()->noContent();
    }

    /** Egasi o'z vitrinasini o'chiradi (rasmlari bilan); keyin yangisini yaratishi mumkin */
    public function destroy(Request $request)
    {
        $request->user()->vitrina()->firstOrFail()->purge();

        return response()->noContent();
    }

    /* ================= Admin ================= */

    public function adminIndex(Request $request)
    {
        return Vitrina::with('user:id,name,username')
            ->withCount('items')
            ->when($request->input('status'), fn ($q, string $status) => $q->where('status', $status))
            ->orderByRaw("status = 'pending' desc")
            ->latest('updated_at')
            ->get();
    }

    /** Admin: nomaqbul vitrinani butunlay o'chirish (rasmlari diskdan ham) */
    public function adminDestroy(int $id)
    {
        Vitrina::findOrFail($id)->purge();

        return response()->noContent();
    }

    public function adminUpdate(Request $request, int $id, TelegramNotifier $notifier)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in([Vitrina::PENDING, Vitrina::APPROVED, Vitrina::REJECTED])],
            'reject_reason' => ['nullable', 'string', 'max:300'],
            'is_verified' => ['sometimes', 'boolean'],
        ]);

        $vitrina = Vitrina::findOrFail($id);
        $vitrina->forceFill([
            ...$data,
            'reject_reason' => ($data['status'] ?? $vitrina->status) === Vitrina::REJECTED ? ($data['reject_reason'] ?? null) : null,
        ])->save();

        if ($vitrina->wasChanged('status')) {
            $this->notifyOwner($vitrina, $notifier);
        }

        return $vitrina->load('user:id,name,username')->loadCount('items');
    }

    /** Tasdiqlandi / rad etildi — egasiga bot xabari (o'z tilida). Yuborilmasa jim o'tadi */
    private function notifyOwner(Vitrina $vitrina, TelegramNotifier $notifier): void
    {
        if (!in_array($vitrina->status, [Vitrina::APPROVED, Vitrina::REJECTED], true) || !$vitrina->user) {
            return;
        }

        $previousLocale = app()->getLocale();
        app()->setLocale($vitrina->user->language ?? 'uz');

        $approved = $vitrina->status === Vitrina::APPROVED;
        $text = $approved
            ? __('bot.vitrina_approved', ['name' => e($vitrina->name)])
            : __('bot.vitrina_rejected', ['name' => e($vitrina->name)])
                . ($vitrina->reject_reason ? "\n\n" . __('bot.vitrina_reason', ['reason' => e($vitrina->reject_reason)]) : '');
        $button = $approved
            ? [__('bot.vitrina_open'), TelegramNotifier::appLink("v_{$vitrina->slug}")]
            : [__('bot.vitrina_edit'), TelegramNotifier::appLink('my_vitrina')];

        $notifier->toUser($vitrina->user, $text, [$button]);

        app()->setLocale($previousLocale);
    }

    /* ================= Yordamchilar ================= */

    private function ownVitrina(Request $request): Vitrina
    {
        abort_if($request->user()->isBlocked(), 403, __('messages.blocked'));
        $vitrina = $request->user()->vitrina()->first();
        abort_unless($vitrina, 422, __('messages.vitrina_create_first'));

        return $vitrina;
    }

    /**
     * Mahsulot maydonlari va rasmlari.
     * images[i] — tartib bilan: eski rasm {src, crop_*} yoki yangi {file, crop_*} (e'lon formasidagi FieldImage kabi).
     */
    private function fillItem(Request $request, Vitrina $vitrina, VitrinaItem $item): VitrinaItem
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'vitrina_section_id' => ['nullable', 'integer', Rule::exists('vitrina_sections', 'id')->where('vitrina_id', $vitrina->id)],
            'price_mode' => ['required', Rule::in(VitrinaItem::PRICE_MODES)],
            'price' => ['nullable', 'integer', 'min:0', 'max:999999999999', 'required_if:price_mode,fixed,from'],
            'price_type_id' => ['nullable', 'integer', Rule::exists('price_types', 'id')],
            'unit' => ['nullable', 'string', 'max:30'],
            'is_available' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array', 'max:' . VitrinaItem::MAX_IMAGES],
            'images.*.src' => ['nullable', 'string'],
            'images.*.file' => ['nullable', 'image', 'max:10240'],
            ...ImageCrop::rules('images.*'),
        ], [
            'images.max' => __('messages.vitrina_too_many_images', ['max' => VitrinaItem::MAX_IMAGES]),
        ]);

        $hasPrice = in_array($data['price_mode'], ['fixed', 'from'], true);
        $item->fill([
            ...collect($data)->except(['images'])->all(),
            'vitrina_section_id' => ($data['vitrina_section_id'] ?? null) ?: null,
            'price' => $hasPrice ? $data['price'] : null,
            'price_type_id' => $hasPrice ? (($data['price_type_id'] ?? null) ?: 1) : null,
            'is_available' => $request->has('is_available') ? $request->boolean('is_available') : ($item->is_available ?? true),
        ]);

        // Eski rasmlardan faqat shu mahsulotniki qoladi (begona yo'l yuborilsa — e'tiborsiz); qolganlari diskdan o'chadi
        $current = array_column($item->images ?? [], 'src');
        $images = [];
        foreach ($data['images'] ?? [] as $index => $image) {
            $file = $request->file("images.{$index}.file");
            if ($file) {
                $images[] = ['src' => VitrinaImage::store($file, 'items', VitrinaImage::ITEM), ...ImageCrop::normalize($image)];
            } elseif (in_array($image['src'] ?? null, $current, true)) {
                $images[] = ['src' => $image['src'], ...ImageCrop::normalize($image)];
            }
        }

        foreach (array_diff($current, array_column($images, 'src')) as $path) {
            VitrinaImage::delete($path);
        }
        $item->images = $images;
        $item->save();

        // Mahsulot o'zgarsa vitrina "yangilangan" bo'ladi (katalogda yuqoriga chiqadi)
        $vitrina->touch();

        return $item->fresh();
    }

    /** "@mebel_lux", "t.me/mebel_lux", "https://instagram.com/mebel.lux" → "mebel_lux" */
    private function handle(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $value = preg_replace('~^(https?://)?(www\.)?(t\.me|telegram\.me|instagram\.com)/~i', '', $value);

        return trim($value, "@/ ") ?: null;
    }
}
