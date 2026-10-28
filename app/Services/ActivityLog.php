<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Foydalanuvchilar faolligi statistikasi — ilovaga yuk bo'lmasligi uchun ikki bosqichda:
 * 1) hodisa kunlik faylga bitta qator bo'lib qo'shiladi (bazaga murojaat yo'q, javobdan keyin);
 * 2) cron (va admin sahifasi ochilganda) faylni o'qib `activity_stats` ga kunlik yig'indi yozadi.
 *
 * Tashrifchi: ro'yxatdan o'tgan bo'lsa user id, mehmon bo'lsa ilova sessiyasi (X-Session).
 */
class ActivityLog
{
    /** Ilova yuboradigan hodisalar (serverga ko'rinmaydiganlari) */
    public const CLIENT_EVENTS = ['app_open', 'page', 'contact_phone', 'contact_telegram'];

    private const KEEP_RAW_DAYS = 14;

    /**
     * @param  array<int, array{e: string, r?: string|int|null}>  $events
     */
    public static function record(array $events, ?int $userId, ?string $session, ?string $client, ?string $locale): void
    {
        if (! $events || app()->runningUnitTests()) {
            return;
        }

        $base = [
            'u' => $userId,
            's' => $session && preg_match('/^[A-Za-z0-9-]{8,40}$/', $session) ? $session : null,
            'c' => in_array($client, ['telegram', 'browser'], true) ? $client : null,
            'l' => in_array($locale, ['uz', 'ru', 'en'], true) ? $locale : null,
        ];

        $lines = '';
        foreach ($events as $event) {
            $ref = $event['r'] ?? null;
            $lines .= json_encode([
                ...$base,
                'e' => $event['e'],
                'r' => $ref === null || $ref === '' ? null : mb_substr((string) $ref, 0, 64),
            ]) . "\n";
        }

        try {
            $path = self::path(now()->toDateString());
            File::ensureDirectoryExists(dirname($path));
            file_put_contents($path, $lines, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // Statistika hech qachon asosiy amalni buzmasin
        }
    }

    /** Bir kunlik faylni yig'ib, shu kun yozuvlarini qaytadan yozadi (qayta-qayta chaqirish xavfsiz) */
    public static function aggregate(string $date): int
    {
        $path = self::path($date);
        if (! is_file($path)) {
            return 0;
        }

        $buckets = [];
        $add = function (string $event, ?string $ref, ?string $visitor) use (&$buckets) {
            $key = $event . "\0" . $ref;
            $buckets[$key] ??= ['event' => $event, 'ref' => $ref, 'hits' => 0, 'visitors' => []];
            $buckets[$key]['hits']++;
            if ($visitor) {
                $buckets[$key]['visitors'][$visitor] = true;
            }
        };

        $handle = fopen($path, 'r');
        while (($line = fgets($handle)) !== false) {
            $row = json_decode($line, true);
            if (! is_array($row) || empty($row['e'])) {
                continue;
            }
            $visitor = ! empty($row['u']) ? "u{$row['u']}" : (! empty($row['s']) ? "s{$row['s']}" : null);

            $add($row['e'], $row['r'] ?? null, $visitor);
            $add('visitors', null, $visitor);
            $add('visitors_registered', null, ! empty($row['u']) ? $visitor : null);
            if (! empty($row['c'])) {
                $add('client', $row['c'], $visitor);
            }
            if (! empty($row['l'])) {
                $add('locale', $row['l'], $visitor);
            }
        }
        fclose($handle);

        $rows = array_map(fn (array $bucket) => [
            'date' => $date,
            'event' => $bucket['event'],
            'ref' => $bucket['ref'],
            'hits' => $bucket['hits'],
            'users' => count($bucket['visitors']),
        ], array_values($buckets));

        DB::transaction(function () use ($date, $rows) {
            DB::table('activity_stats')->where('date', $date)->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('activity_stats')->insert($chunk);
            }
        });

        return count($rows);
    }

    public static function pruneRawFiles(): void
    {
        $threshold = now()->subDays(self::KEEP_RAW_DAYS)->toDateString();
        foreach (File::glob(storage_path('app/activity/*.log')) as $file) {
            if (basename($file, '.log') < $threshold) {
                @unlink($file);
            }
        }
    }

    private static function path(string $date): string
    {
        return storage_path("app/activity/{$date}.log");
    }
}
