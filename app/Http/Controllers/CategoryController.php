<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductFilters;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
class CategoryController extends Controller
{
	//
	public function index()
	{
		return Category::select('id', 'name', 'translations')->get();
	}

	public function show($id)
	{
		return Category::with(['parameters', 'parent'])
			->findOrFail($id);
	}
	public function parents($parent_id = null)
	{
		$key = 'categories:v' . Category::cacheVersion() . ':parents:' . ($parent_id ?? 'root');

		return Cache::remember($key, now()->addMinutes(15), function () use ($parent_id, $key) {
			return Category::where('parent_id', $parent_id)
				->with('children')
				->get();
		});
	}


	/**
	 * Har bir kategoriyadagi faol e'lonlar soni: { category_id: soni }.
	 * Bitta GROUP BY so'rov, 5 daqiqa keshlanadi. Ota bo'limlar soni frontendda ichki bo'limlardan yig'iladi.
	 */
	public function counts()
	{
		return Cache::remember('categories:active_counts', now()->addMinutes(5), function () {
			return Product::active()
				->selectRaw('category_id, COUNT(*) as total')
				->groupBy('category_id')
				->pluck('total', 'category_id')
				->map(fn($total) => (int) $total);
		});
	}

	/**
	 * Bosh sahifadagi "Tezkor bo'limlar": admin belgilagan kategoriyalar (istalgan darajadagi), tartib bo'yicha.
	 */
	public function featured()
	{
		return Cache::remember('categories:v' . Category::cacheVersion() . ':featured', now()->addMinutes(15), function () {
			return Category::where('is_featured', true)
				->orderBy('featured_order')
				->orderBy('id')
				->get(['id', 'name', 'translations', 'image', 'parent_id', 'is_page', 'is_permanent']);
		});
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			'parent_id' => [
				'nullable',
				'exists:categories,id',
				function ($attribute, $value, $fail) {
					// Tanlangan ota-ona (parent) kategoriya is_page: true bo'lmasligi kerak
					$parent = Category::find($value);
					if ($parent && $parent->is_page) {
						$fail("Tanlangan kategoriya 'Sahifa' turida. Unga ichki kategoriya qo'shib bo'lmaydi.");
					}
				},
			],
			'name' => 'required|string',
			'image' => 'sometimes|mimes:svg',
			'listing_duration_days' => 'nullable|integer|min:1|max:365',
			'featured_order' => 'nullable|integer|min:0|max:999',
			...Category::translationRules(),
		]);

		$validated['is_page'] = $request->boolean('is_page');
		$validated = [...$validated, ...self::flags($request)];
		if ($request->hasFile('image')) {
			$file = $request->file('image');
			// Fayl nomini xavfsiz qilish (Original nomda keraksiz belgilar bo'lishi mumkin)
			$fileName = time() . '_' . $request->name . '.' . $file->getClientOriginalExtension();

			// public/icons papkasiga yuklash
			$file->move(public_path('icons'), $fileName);

			// Validatsiya qilingan ma'lumotlarga yo'lni qo'shamiz
			$validated['image'] = '/icons/' . $fileName;
		}

		$category = new Category($validated);
		$category->fillTranslations($request->all())->save();
		return response()->json($category, 201);
	}


	public function update(Request $request, $id)
	{
		$category = Category::findOrFail($id);
		if ($request->has('image') && !$request->hasFile('image')) {
			$request->request->remove('image');
		}
		$request->validate([
			'name' => 'string',
			'image' => 'sometimes|mimes:svg', // image ekanligini ham tekshirish yaxshi
			'listing_duration_days' => 'sometimes|nullable|integer|min:1|max:365',
			'featured_order' => 'sometimes|nullable|integer|min:0|max:999',
			...Category::translationRules(),
		]);

		$data = $request->all(); // Barcha ma'lumotlarni o'zgaruvchiga olamiz
		$data['is_page'] = $request->boolean('is_page');
		$data = [...$data, ...self::flags($request)];
		if (! $request->filled('listing_duration_days')) {
			unset($data['listing_duration_days']);
		}

		if ($request->hasFile('image')) {
			// 1. Eski faylni o'chirish logikasi
			if ($category->image) {
				$oldFilePath = public_path($category->image); // /icons/file.jpg ni to'liq yo'lga aylantiradi

				if (File::exists($oldFilePath)) {
					File::delete($oldFilePath);
				}
			}

			// 2. Yangi faylni yuklash
			$file = $request->file('image');
			$fileName = time() . '_' . $category->name . '.' . $file->getClientOriginalExtension();
			$file->move(public_path('icons'), $fileName);

			// Yangi pathni ma'lumotlar massiviga qo'shamiz
			$data['image'] = '/icons/' . $fileName;
		}

		$category->fill($data)->fillTranslations($request->all())->save();

		if ($category->wasChanged('is_permanent')) {
			self::syncPermanentListings($category);
		}

		return response()->json($category, 200);
	}

	/** Formadagi kalitlar: multipart'da "true"/"false" satr bo'lib keladi */
	private static function flags(Request $request): array
	{
		return [
			'with_price' => $request->boolean('with_price'),
			'with_image' => $request->boolean('with_image'),
			// Kalit umuman kelmasa — sarlavha so'raladi (eski xatti-harakat)
			'with_title' => $request->has('with_title') ? $request->boolean('with_title') : true,
			'is_permanent' => $request->boolean('is_permanent'),
			'is_featured' => $request->boolean('is_featured'),
			'featured_order' => (int) $request->input('featured_order', 0),
			// Bo'sh qoldirilsa — joriy (yoki bazadagi standart) muddat o'zgarmaydi
			...($request->filled('listing_duration_days')
				? ['listing_duration_days' => (int) $request->input('listing_duration_days')]
				: []),
		];
	}

	/**
	 * "Muddatsiz" yoqilsa — faol e'lonlar muddatsiz bo'ladi; o'chirilsa — muddatsizlar oddiy muddat oladi.
	 * Muddati o'tib bo'lgan e'lonlarga tegilmaydi.
	 */
	private static function syncPermanentListings(Category $category): void
	{
		if ($category->is_permanent) {
			Product::where('category_id', $category->id)->active()
				->update(['expires_at' => null]);
			return;
		}

		Product::where('category_id', $category->id)
			->whereNull('expires_at')
			->update(['expires_at' => $category->listingExpiresAt()]);
	}

	public function changeParent($id, Request $request)
	{
		// 1. O'zgartirilayotgan kategoriyani topamiz
		$category = Category::findOrFail($id);
		$newParentId = $request->parent_id;

		// 2. Agar yangi parent tanlangan bo'lsa (null bo'lmasa)
		if ($newParentId) {
			// O'zini o'ziga parent qilib qo'yishni oldini olamiz
			if ($id == $newParentId) {
				return response()->json(['message' => "Kategoriyani o'zini o'ziga ota qilib bo'lmaydi."], 422);
			}

			$parent = Category::findOrFail($newParentId);

			// 3. TANQIDIY TEKSHIRUV: Yangi parent "is_page" bo'lmasligi kerak
			if ($parent->is_page) {
				return response()->json([
					'message' => "Siz tanlagan '{$parent->name}' kategoriyasi sahifa (leaf node) hisoblanadi. Uning ichiga boshqa kategoriya qo'shib bo'lmaydi."
				], 422);
			}
		}

		// 4. Hammasi joyida bo'lsa, saqlaymiz
		$category->parent_id = $newParentId;
		$category->save();

		return response()->json([
			'message' => "Kategoriya joylashuvi muvaffaqiyatli o'zgartirildi",
			'category' => $category
		], 200);
	}

	/**
	 * Kategoriya e'lonlari + uning parametrlari (frontend ulardan filtrlarni quradi).
	 * Filtrlar: App\Services\ProductFilters (shahar, narx, rasmli, parametrlar, saralash).
	 */
	public function products(Request $request, $categoryId)
	{
		$category = Category::with(['parent', 'parameters'])->findOrFail($categoryId);

		$query = Product::withExists([
			'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
		])
			->where('category_id', $category->id)
			->active();

		ProductFilters::apply($query, $request);
		ProductFilters::sort($query, $request->input('sort'));

		$category->setRelation('products', $query->get());

		return $category;
	}

	public function destroy($id)
	{
		$category = Category::findOrFail($id);
		$category->delete(); // Faqat bazada deleted_at vaqti yoziladi

		return response()->json(['message' => 'Kategoriya savatga tashlandi']);
	}

	public function forceDelete($id)
	{
		// Soft delete bo'lganlarni ham ichidan qidirish uchun withTrashed() ishlatamiz
		$category = Category::withTrashed()->findOrFail($id);

		// 1. Faylni serverdan o'chirish
		if ($category->image) {
			$filePath = public_path($category->image);
			if (File::exists($filePath)) {
				File::delete($filePath);
			}
		}

		// 2. Bazadan butunlay o'chirish
		$category->forceDelete();

		return response()->json(['message' => 'Kategoriya va rasm butunlay yo\'qotildi']);
	}
}
