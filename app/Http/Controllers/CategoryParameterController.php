<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
class CategoryParameterController extends Controller
{
    // Create or Update
    public function store(Request $request, Category $category)
    {
        // 1. TEKSHIRUV: Agar kategoriya "Sahifa" bo'lmasa, parametrlarni saqlashni taqiqlaymiz
        if (!$category->is_page) {
            return response()->json([
                'message' => "Parametrlarni faqat 'Sahifa' (Page) turidagi kategoriyalarga biriktirish mumkin. '{$category->name}' esa papka hisoblanadi."
            ], 422);
        }
        $syncData = [];

        foreach ($request->all() as $item) {
            // parameter_id ni kalit sifatida, qolganlarini qiymat sifatida yig'amiz
            $syncData[$item['parameter_id']] = [
                'is_required' => $item['is_required'] ?? false,
                'sort_order' => $item['sort_order'] ?? 0,
            ];
        }

        // syncWithoutDetaching mavjudlarini yangilaydi, yo'qlarini qo'shadi. 
        // Eski, massivda bo'lmaganlarini o'chirib tashlamaydi.
        $category->parameters()->sync($syncData);

        return response()->json($category->load('parameters'), 201);
    }

    public function index(Category $category)
    {
        // Faqat shu kategoriyaga tegishli parametrlarni olish
        return response()->json($category->parameters);
    }
}
