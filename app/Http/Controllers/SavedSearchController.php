<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SavedSearch;
use Illuminate\Http\Request;

/**
 * Qidiruvni kuzatish: foydalanuvchi so'rov + filtrlarni saqlaydi, mos yangi e'lon chiqsa bot xabar beradi
 * (App\Console\Commands\NotifySavedSearches).
 */
class SavedSearchController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->savedSearches()->latest()->get();
    }

    public function show(Request $request, int $id)
    {
        return $request->user()->savedSearches()->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'label' => ['required', 'string', 'max:200'],
            'filters' => ['nullable', 'array'],
            'params' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        abort_if(
            $user->savedSearches()->count() >= SavedSearch::MAX_PER_USER,
            422,
            __('messages.saved_search_limit', ['max' => SavedSearch::MAX_PER_USER]),
        );

        return $user->savedSearches()->create([
            ...$validated,
            // Faqat bundan keyin chiqadigan e'lonlar haqida xabar beriladi
            'last_product_id' => (int) Product::withTrashed()->max('id'),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate(['is_paused' => ['required', 'boolean']]);

        $search = $request->user()->savedSearches()->findOrFail($id);
        $attributes = $validated;
        // Pauzadan qaytganda pauza paytidagi e'lonlar to'planib bir yo'la kelmasin
        if ($search->is_paused && ! $validated['is_paused']) {
            $attributes['last_product_id'] = (int) Product::withTrashed()->max('id');
        }
        $search->update($attributes);

        return $search;
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->savedSearches()->findOrFail($id)->delete();

        return response()->noContent();
    }
}
