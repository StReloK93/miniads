<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Kanaldagi "jonli" post: tepaga qadalgan bitta xabar, unda tezkor bo'limlardagi faol e'lonlar soni.
 * Yangi post yozilmaydi — o'sha xabar tahrirlanadi (Telegram tahrirda bildirishnoma yubormaydi, kanal spam bo'lmaydi).
 * Raqamlar o'zgarmagan bo'lsa Telegram'ga umuman so'rov ketmaydi.
 */
class ChannelLivePostService
{
    public const ENABLED = 'channel_live_post_enabled';

    /** Har kanal uchun holat: {"message_id": 123, "hash": "..."} */
    private const STATE_PREFIX = 'channel_live_post:';

    /** Kategoriya ikonkasi (lucide nomi) → kanal matnidagi emoji */
    private const EMOJI = [
        'UtensilsCrossed' => '🍽',
        'ArrowLeftRight' => '💳',
        'CarTaxiFront' => '🚕',
        'Wrench' => '🔧',
        'House' => '🏠',
        'Car' => '🚗',
        'MonitorSmartphone' => '📱',
        'BriefcaseBusiness' => '💼',
        'Armchair' => '🪑',
        'GraduationCap' => '🎓',
        'Cat' => '🐾',
        'Shirt' => '👕',
        'Gift' => '🎁',
    ];

    public static function enabled(): bool
    {
        return Setting::getValue(self::ENABLED) === '1';
    }

    public static function setEnabled(bool $enabled): void
    {
        Setting::setValue(self::ENABLED, $enabled ? '1' : '0');
    }

    /** Kanalda jonli post turibdimi (admin sahifasi uchun) */
    public static function isPosted(): bool
    {
        return collect(TelegramProductService::channels())
            ->contains(fn (string $channelId) => isset(self::state($channelId)['message_id']));
    }

    /**
     * Post matni va tugmalari. Vaqt qatori alohida: raqamlar o'zgarmasa post qayta tahrirlanmaydi.
     *
     * @return array{body: string, buttons: array<int, array{0: string, 1: string}>}
     */
    public function content(): array
    {
        $categories = Category::where('is_featured', true)->orderBy('featured_order')->orderBy('id')->get();

        $stats = Product::active()
            ->setEagerLoads([])
            ->selectRaw('category_id, COUNT(*) as total, SUM(CASE WHEN published_at >= ? THEN 1 ELSE 0 END) as today', [today()])
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $children = Category::withTrashed()->get(['id', 'parent_id'])->groupBy('parent_id');
        $appUrl = rtrim((string) config('services.telegram.mini_app_url'), '/');

        $lines = [];
        $buttons = [];
        foreach ($categories as $category) {
            $ids = $this->withDescendants($category->id, $children);
            $total = $ids->sum(fn (int $id) => (int) ($stats[$id]->total ?? 0));
            $today = $ids->sum(fn (int $id) => (int) ($stats[$id]->today ?? 0));

            $emoji = self::EMOJI[$category->image] ?? '📌';
            $name = $category->getRawOriginal('name');

            // E'loni yo'q bo'lim matnda chiqmaydi (tugmasi qoladi — bosib, birinchi bo'lib joylash mumkin)
            if ($total > 0) {
                $line = __('bot.live_line', ['emoji' => $emoji, 'name' => htmlspecialchars($name, ENT_NOQUOTES), 'count' => $total], 'uz');
                if ($today > 0) {
                    $line .= __('bot.live_today', ['count' => $today], 'uz');
                }
                $lines[] = $line;
            }

            // Ichki bo'limlari bor kategoriya (Xizmatlar) — bo'limlar ro'yxati, aks holda e'lonlar sahifasi
            $start = ($category->is_page ? 'cat_' : 'cats_') . $category->id;
            $buttons[] = ["{$emoji} {$name}", "{$appUrl}?startapp={$start}"];
        }

        $buttons[] = [__('bot.live_post_ad', [], 'uz'), "{$appUrl}?startapp=create"];

        $body = implode("\n", [
            __('bot.live_title', [], 'uz'),
            '',
            ...$lines,
            '',
            __('bot.live_total', ['count' => (int) $stats->sum('total')], 'uz'),
        ]);

        return ['body' => $body, 'buttons' => $buttons];
    }

    /**
     * Har kanalda postni yangilaydi: yo'q bo'lsa — yozib, qadab qo'yadi; bor bo'lsa — tahrirlaydi.
     *
     * @return array<string, string> kanal → natija (sent | edited | unchanged | failed)
     */
    public function sync(bool $force = false): array
    {
        ['body' => $body, 'buttons' => $buttons] = $this->content();
        $hash = md5($body . json_encode($buttons));
        $html = $body . "\n" . __('bot.live_updated', ['time' => now()->format('H:i')], 'uz');
        $keyboard = Keyboard::make()
            ->buttons(array_map(fn (array $button) => Button::make($button[0])->url($button[1]), $buttons))
            ->chunk(2);

        $results = [];
        foreach (TelegramProductService::channels() as $channelId) {
            $state = self::state($channelId);

            if (! $force && isset($state['message_id']) && ($state['hash'] ?? null) === $hash) {
                $results[$channelId] = 'unchanged';
                continue;
            }

            if (isset($state['message_id'])) {
                $response = Telegraph::chat($channelId)->edit((int) $state['message_id'])
                    ->html($html)->keyboard($keyboard)->withoutPreview()->send();
                $description = (string) $response->json('description');

                if ($response->telegraphOk() || Str::contains($description, 'message is not modified')) {
                    self::saveState($channelId, ['message_id' => $state['message_id'], 'hash' => $hash]);
                    $results[$channelId] = 'edited';
                    continue;
                }

                // Post kanaldan qo'lda o'chirilgan bo'lsa — yangisini yozamiz; boshqa xatoda keyingi safar urinamiz
                if (! Str::contains($description, ['message to edit not found', "message can't be edited", 'MESSAGE_ID_INVALID'])) {
                    Log::warning('Jonli post tahrirlanmadi', ['channel_id' => $channelId, 'description' => $description]);
                    $results[$channelId] = 'failed';
                    continue;
                }
            }

            $response = Telegraph::chat($channelId)->html($html)->keyboard($keyboard)->withoutPreview()->silent()->send();
            if (! $response->telegraphOk()) {
                Log::error('Jonli post yuborilmadi', ['channel_id' => $channelId, 'description' => $response->json('description')]);
                $results[$channelId] = 'failed';
                continue;
            }

            $messageId = (int) $response->telegraphMessageId();
            // Qadash ham ovozsiz — a'zolarga "post qadaldi" bildirishnomasi bormaydi
            Telegraph::chat($channelId)->pinMessage($messageId)->silent()->send();
            self::saveState($channelId, ['message_id' => $messageId, 'hash' => $hash]);
            $results[$channelId] = 'sent';
        }

        return $results;
    }

    /** O'chirilganda: post kanaldan olib tashlanadi */
    public function remove(): void
    {
        foreach (TelegramProductService::channels() as $channelId) {
            $messageId = self::state($channelId)['message_id'] ?? null;
            if ($messageId) {
                Telegraph::chat($channelId)->deleteMessage((int) $messageId)->send();
            }
            Setting::setValue(self::STATE_PREFIX . $channelId, null);
        }
    }

    /** @param Collection<int|string, Collection<int, Category>> $children parent_id → bolalari */
    private function withDescendants(int $id, Collection $children): Collection
    {
        $ids = collect([$id]);
        foreach ($children->get($id, collect()) as $child) {
            $ids = $ids->merge($this->withDescendants($child->id, $children));
        }

        return $ids;
    }

    private static function state(string $channelId): array
    {
        return json_decode((string) Setting::getValue(self::STATE_PREFIX . $channelId), true) ?: [];
    }

    private static function saveState(string $channelId, array $state): void
    {
        Setting::setValue(self::STATE_PREFIX . $channelId, json_encode($state));
    }
}
