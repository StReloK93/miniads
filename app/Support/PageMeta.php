<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Sahifa sarlavhasi va havola kartasi (Open Graph). Havola Telegram yoki boshqa joyda ulashilganda
 * rasm, sarlavha va narx bilan chiroyli karta bo'lib chiqadi. Sayt — SPA, shuning uchun meta server tomonda qo'yiladi.
 */
class PageMeta
{
    public const SITE_NAME = "Navoiy e'lonlari";

    /** @return array{title: string, description: string, image: ?string, url: string, type: string} */
    public static function default(): array
    {
        return [
            'title' => self::SITE_NAME,
            'description' => "Navoiy viloyati bo'yicha e'lonlar: uy-joy, avto, ish, xizmatlar va boshqalar. E'lon joylash bepul.",
            'image' => null,
            'url' => url()->current(),
            'type' => 'website',
        ];
    }

    /** Faol e'lon uchun; o'chirilgan yoki muddati o'tgan bo'lsa — umumiy sayt kartasi (yopiq e'lon ma'lumoti chiqmasin) */
    public static function forProduct(int $id): array
    {
        $product = Product::active()->with(['images', 'price_type', 'district'])->find($id);
        if (! $product) {
            return self::default();
        }

        $price = $product->price === null
            ? 'Kelishiladi'
            : number_format((float) $product->price, 0, '.', ' ') . ($product->price_type?->type ? ' ' . $product->price_type->type : '');
        $summary = collect([$price, $product->district?->name])->filter()->implode(' · ');
        $description = trim($summary . ($product->description ? ' — ' . Str::limit(Str::squish($product->description), 160) : ''));

        $image = $product->images->first();

        return [
            'title' => Str::limit($product->title, 90) . ' — ' . self::SITE_NAME,
            'description' => $description,
            'image' => $image ? url('storage/' . ltrim($image->src, '/')) : null,
            'url' => url("product/{$product->id}"),
            'type' => 'product',
        ];
    }
}
