<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\File;
class CategoryController extends Controller
{
	//
	public function index()
	{
		return Category::select('id', 'name')->get();
	}

	public function show($id)
	{
		return Category::with(['parameters'])
			->with('parent')
			->select('id', 'name', 'image', 'parent_id', 'is_page')
			->findOrFail($id);
	}
	public function parents($parent_id = null)
	{
		$breadcrumbs = null;
		if ($parent_id) {
			$parent = Category::findOrFail($parent_id);
			$breadcrumbs = $parent->getBreadcrumbs();
		}

		$categories = Category::where('parent_id', $parent_id)
			->with('parent')
			->select('id', 'name', 'image', 'parent_id', 'is_page')
			->get();

		return response()->json([
			'breadcrumbs' => $breadcrumbs,
			'categories' => $categories,
		]);
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
		]);

		$validated['is_page'] = $request->boolean('is_page');
		if ($request->hasFile('image')) {
			$file = $request->file('image');
			// Fayl nomini xavfsiz qilish (Original nomda keraksiz belgilar bo'lishi mumkin)
			$fileName = time() . '_' . $request->name . '.' . $file->getClientOriginalExtension();

			// public/icons papkasiga yuklash
			$file->move(public_path('icons'), $fileName);

			// Validatsiya qilingan ma'lumotlarga yo'lni qo'shamiz
			$validated['image'] = '/icons/' . $fileName;
		}

		$category = Category::create($validated);
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
		]);

		$data = $request->all(); // Barcha ma'lumotlarni o'zgaruvchiga olamiz
		$data['is_page'] = $request->boolean('is_page');

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


		$category->update($data);

		return response()->json($category, 200);
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
				return response()->json(['message' => 'Kategoriyani o‘zini o‘ziga ota qilib bo‘lmaydi.'], 422);
			}

			$parent = Category::findOrFail($newParentId);

			// 3. TANQIDIY TEKSHIRUV: Yangi parent "is_page" bo'lmasligi kerak
			if ($parent->is_page) {
				return response()->json([
					'message' => "Siz tanlagan '{$parent->name}' kategoriyasi sahifa (leaf node) hisoblanadi. Uning ichiga boshqa kategoriya qo‘shib bo‘lmaydi."
				], 422);
			}
		}

		// 4. Hammasi joyida bo'lsa, saqlaymiz
		$category->parent_id = $newParentId;
		$category->save();

		return response()->json([
			'message' => 'Kategoriya joylashuvi muvaffaqiyatli o‘zgartirildi',
			'category' => $category
		], 200);
	}

	public function products($categoryId)
	{
		$Category = Category::with('products', 'parent')->findOrFail($categoryId);
		return $Category;
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
