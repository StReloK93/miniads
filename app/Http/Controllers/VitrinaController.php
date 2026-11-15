<?php

namespace App\Http\Controllers;

use App\Models\Vitrina;
use App\Models\VitrinaItem;
use App\Models\VitrinaSection;
use App\Support\Phone;
use App\Support\VitrinaImage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Vitrinalar: ommaviy katalog va sahifa, egasi uchun tahrirlash (o'z vitrinasi, bo'limlar, mahsulotlar),
 * admin uchun tasdiqlash. Bir foydalanuvchi — bitta vitrina.
 */
class VitrinaController extends Controller
{
    /* ================= Ommaviy ================= */

    /** Katalog: tasdiqlanganlar; city_id — shu shahar va butun viloyat vitrinalari */
    public function index(Request $request)
    {
        $request->validate([
            'type' => ['nullable', Rule::in(Vitrina::TYPES)],
            'city_id' => ['nullable', 'integer'],
        ]);

        return Vitrina::approved()
            ->withCount('items')
            ->when($request->integer('city_id'), fn ($q, int $cityId) => $q->where(fn ($q) => $q
                ->where('district_id', $cityId)
                ->orWhereNull('district_id')))
            ->when($request->input('type'), fn ($q, string $type) => $q->where('type', $type))
            ->orderByDesc('is_verified')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['id', 'name', 'slug', 'type', 'logo', 'cover', 'description', 'district_id', 'is_verified', 'updated_at']);
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

    public function destroyItem(Request $request, int $id)
    {
        $item = $this->ownVitrina($request)->items()->findOrFail($id);
        foreach ($item->images ?? [] as $path) {
            VitrinaImage::delete($path);
        }
        $item->delete();

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

    public function adminUpdate(Request $request, int $id)
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

        return $vitrina->load('user:id,name,username')->loadCount('items');
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
     * Mahsulot maydonlari va rasmlari. keep_images[] — qoladigan eski rasmlar (tartibi bilan), images[] — yangilari.
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
            'keep_images' => ['nullable', 'array'],
            'keep_images.*' => ['string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $hasPrice = in_array($data['price_mode'], ['fixed', 'from'], true);
        $item->fill([
            ...collect($data)->except(['keep_images', 'images'])->all(),
            'vitrina_section_id' => ($data['vitrina_section_id'] ?? null) ?: null,
            'price' => $hasPrice ? $data['price'] : null,
            'price_type_id' => $hasPrice ? (($data['price_type_id'] ?? null) ?: 1) : null,
            'is_available' => $request->has('is_available') ? $request->boolean('is_available') : ($item->is_available ?? true),
        ]);

        // Eski rasmlardan faqat shu mahsulotniki qoladi; qolganlari diskdan o'chadi
        $current = $item->images ?? [];
        $keep = array_values(array_intersect($data['keep_images'] ?? [], $current));
        $newFiles = $request->file('images', []);
        abort_if(count($keep) + count($newFiles) > VitrinaItem::MAX_IMAGES, 422,
            __('messages.vitrina_too_many_images', ['max' => VitrinaItem::MAX_IMAGES]));

        foreach (array_diff($current, $keep) as $path) {
            VitrinaImage::delete($path);
        }
        foreach ($newFiles as $file) {
            $keep[] = VitrinaImage::store($file, 'items', VitrinaImage::ITEM);
        }
        $item->images = $keep;
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
