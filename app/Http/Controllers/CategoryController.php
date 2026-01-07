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
		return Category::without('children')->select('id', 'name')->get();
	}

	public function show($id)
	{
		return Category::without('children')->select('id', 'name', 'image', 'parent_id')->findOrFail($id);
	}
	public function parents()
	{
		return Category::whereNull('parent_id')->select('id', 'name', 'image', 'parent_id')->get();
	}


	public function store(Request $request)
	{
		$validated = $request->validate([
			'parent_id' => 'nullable|exists:categories,id',
			'name' => 'required|string',
			'image' => 'sometimes|mimes:svg',
		]);

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
		$category = Category::findOrFail($id);
		$category->parent_id = $request->parent_id;
		$category->save();

		return response()->json($category, 200);

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
