<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use Illuminate\Http\Request;

/**
 * Foydalanuvchi tomoni: administratsiyadan kelgan xabarlar. Ilova Profil'da ko'rsatadi,
 * o'qilmaganlari pastki menyuda belgi (son) bo'lib turadi — ekranni to'sib qo'ymaydi.
 */
class AdminMessageController extends Controller
{
    public function index(Request $request)
    {
        return AdminMessage::where('user_id', $request->user()->id)
            ->with('product:id,title')
            ->latest('id')
            ->take(30)
            ->get(['id', 'product_id', 'type', 'body', 'read_at', 'created_at']);
    }

    /** Profil'da ko'rilgan xabarlar o'qildi */
    public function markRead(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:50'],
            'ids.*' => ['integer'],
        ]);

        AdminMessage::where('user_id', $request->user()->id)
            ->whereIn('id', $validated['ids'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->noContent();
    }
}
