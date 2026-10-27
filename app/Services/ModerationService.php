<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReport;
use App\Models\Setting;
use App\Models\User;
use App\Support\UzbekText;

/**
 * Avtomatik moderatsiya va admin ogohlantirishlari.
 * Taqiqlangan so'z topilsa e'lon o'chirilmaydi — "Shikoyat" navbatiga tushadi, qarorni admin qiladi.
 */
class ModerationService
{
    /** Shu songa yetganda admin chatiga ogohlantirish ketadi (har shikoyatda emas — spam bo'lmasin) */
    private const ALERT_AT = [1, 3];

    public function __construct(private TelegramNotifier $notifier)
    {
    }

    /** E'lon matnida taqiqlangan so'z bo'lsa — tizim nomidan shikoyat */
    public function scan(Product $product): void
    {
        $words = Setting::bannedWords();
        if (! $words) {
            return;
        }

        $text = ' ' . UzbekText::normalize($product->title . ' ' . $product->description) . ' ';
        // Uzun so'z qo'shimchalari bilan ham topiladi ("qurol" → "qurollar"); qisqasi faqat butun so'z sifatida
        $found = collect($words)->first(fn (string $word) => mb_strlen($word) >= 4
            ? str_contains($text, " {$word}")
            : str_contains($text, " {$word} "));
        if (! $found) {
            return;
        }

        ProductReport::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => User::getBotUser()->id],
            ['reason' => 'prohibited', 'comment' => "Avtomatik: «{$found}» so'zi", 'resolved_at' => null],
        );
        $this->reportAdded($product);
    }

    /** Yangi shikoyatdan keyin: ochiq shikoyatlar soni 1 yoki 3 ga yetsa — admin chatiga xabar */
    public function reportAdded(Product $product): void
    {
        $count = $product->openReports()->count();
        if (! in_array($count, self::ALERT_AT, true)) {
            return;
        }

        $latest = $product->openReports()->latest('updated_at')->first();
        $title = e($product->title);
        $reason = e($latest?->reason_label ?? '');
        $comment = $latest?->comment ? "\n<i>" . e($latest->comment) . '</i>' : '';
        $heading = $count >= 3 ? "⚠️ <b>{$count} ta shikoyat</b>" : '🚩 <b>Yangi shikoyat</b>';

        $this->notifier->toAdmins(
            "{$heading}\n«{$title}» (#{$product->id})\nSabab: {$reason}{$comment}",
            [["Ko'rib chiqish", TelegramNotifier::appLink('admin_reported')]],
        );
    }
}
