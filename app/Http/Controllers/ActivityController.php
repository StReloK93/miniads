<?php

namespace App\Http\Controllers;

use App\Services\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ilova yuboradigan faollik paketlari (ilova ochilishi, sahifa o'tishlari, aloqa tugmalari).
 * Ilova ularni ~30 soniyada bir marta, bitta so'rovda yuboradi.
 */
class ActivityController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'max:100'],
            'events.*.e' => ['required', 'string', Rule::in(ActivityLog::CLIENT_EVENTS)],
            'events.*.r' => ['nullable', 'string', 'max:64'],
        ]);

        ActivityLog::record(
            $data['events'],
            $request->user()?->id,
            $request->header('X-Session'),
            $request->header('X-Client'),
            app()->getLocale(),
        );

        return response()->noContent();
    }
}
