<?php

namespace App\Http\Controllers;

use App\Services\ErrorAlert;
use Illuminate\Http\Request;

/**
 * Ilovadagi (brauzer / Telegram mini app) xatolar: forma yuborilmadi, JS xatosi.
 * Server bunday xatolarni o'zi ko'rmaydi (internet, nginx 413/502, kod xatosi) — ilova shu yerga yuboradi.
 */
class ClientErrorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', 'string', 'in:request,js'],
            'message' => ['required', 'string', 'max:500'],
            'page' => ['nullable', 'string', 'max:300'],
            'details' => ['nullable', 'array'],
        ]);

        $details = $data['details'] ?? [];
        $title = $data['kind'] === 'js' ? 'Ilovada JS xatosi' : 'Ilovada so\'rov bajarilmadi';

        ErrorAlert::send(
            "client:{$data['kind']}:{$data['message']}",
            $title,
            [
                'Xabar' => $data['message'],
                'Sahifa' => $data['page'] ?? null,
                'Foydalanuvchi' => $request->user()?->id,
                'Ilova' => $request->header('X-Client'),
                'Tafsilot' => $details ? $details : null,
                'Brauzer' => $request->userAgent(),
            ],
        );

        return response()->noContent();
    }
}
