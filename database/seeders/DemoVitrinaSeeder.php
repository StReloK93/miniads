<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\User;
use App\Models\Vitrina;
use App\Models\VitrinaItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Lokal sinov uchun vitrinalar: har turdan (do'kon, menyu, portfolio, narxnoma, ko'chmas mulk).
 * Bittasi "kutilmoqda" holatida — admin panelda tasdiqlashni sinash uchun.
 * Ishga tushirish: php artisan db:seed --class=DemoVitrinaSeeder (qayta ishga tushirsa — takrorlanmaydi)
 */
class DemoVitrinaSeeder extends Seeder
{
    private const UZS = 1;
    private const USD = 2;

    public function run(): void
    {
        $districts = District::pluck('id', 'name');

        foreach ($this->vitrinas() as $index => $data) {
            $owner = User::firstOrCreate(
                ['username' => $data['owner']],
                ['name' => $data['owner_name'], 'role' => 'user'],
            );
            if ($owner->vitrina()->exists()) {
                continue;
            }

            $vitrina = new Vitrina([
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'],
                'district_id' => $districts[$data['city']] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'telegram' => $data['owner'],
                'instagram' => $data['instagram'] ?? null,
                'work_hours' => $data['work_hours'] ?? null,
            ]);
            $vitrina->user_id = $owner->id;
            $vitrina->slug = Vitrina::uniqueSlug($data['name']);
            $vitrina->status = $data['status'] ?? Vitrina::APPROVED;
            $vitrina->is_verified = $data['verified'] ?? false;
            $vitrina->views_count = 40 + $index * 37;
            $vitrina->save();

            $position = 0;
            foreach ($data['sections'] as $sectionName => $items) {
                $section = $vitrina->sections()->create(['name' => $sectionName, 'position' => $position]);

                foreach ($items as [$title, $description, $priceMode, $price, $unit]) {
                    // Rasmli kartalarni ko'rish uchun: do'kon va uylarda har mahsulotga, portfolioda faqat "ishlar"ga
                    $withPhotos = in_array($data['type'], ['shop', 'realty'], true)
                        || ($data['type'] === 'portfolio' && $priceMode === 'none');
                    $photoCount = $withPhotos ? ($data['type'] === 'shop' ? 1 : 3) : 0;
                    $images = [];
                    for ($i = 0; $i < $photoCount; $i++) {
                        $images[] = $this->demoImage($title, self::TONES[$data['type']], $i);
                    }

                    $item = new VitrinaItem([
                        'vitrina_section_id' => $section->id,
                        'title' => $title,
                        'description' => $description,
                        'price_mode' => $priceMode,
                        'price' => in_array($priceMode, ['fixed', 'from'], true) ? $price : null,
                        'price_type_id' => in_array($priceMode, ['fixed', 'from'], true)
                            // Dollar vitrinasida ham ijara so'mda (million so'mdan katta son — so'm)
                            ? ($data['currency'] === self::USD && $price >= 1_000_000 ? self::UZS : $data['currency'])
                            : null,
                        'unit' => $unit,
                        'position' => $position++,
                    ]);
                    $item->images = $images;
                    $vitrina->items()->save($item);
                }
            }
        }
    }

    /** Vitrina turi rangi (frontend vitrinaKit bilan bir xil) */
    private const TONES = [
        'shop' => [59, 130, 246],
        'menu' => [245, 158, 11],
        'portfolio' => [139, 92, 246],
        'pricelist' => [20, 184, 166],
        'realty' => [236, 72, 153],
    ];

    /**
     * Demo rasm: rangli gradient va ustida mahsulot nomi (haqiqiy rasm o'rniga, faqat sinov uchun).
     * Har rasm biroz boshqa tusda — galereyada farqi ko'rinsin.
     */
    private function demoImage(string $title, array $tone, int $variant): string
    {
        $size = 800;
        $image = imagecreatetruecolor($size, $size);
        $shift = $variant * 35;
        [$r, $g, $b] = [($tone[0] + $shift) % 256, ($tone[1] + $shift / 2) % 256, $tone[2]];

        for ($y = 0; $y < $size; $y++) {
            $k = $y / $size;
            $color = imagecolorallocate($image,
                (int) ($r + (255 - $r) * $k * 0.55),
                (int) ($g + (255 - $g) * $k * 0.55),
                (int) ($b + (255 - $b) * $k * 0.55));
            imageline($image, 0, $y, $size, $y, $color);
        }

        $font = collect(['C:/Windows/Fonts/segoeuib.ttf', 'C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'])
            ->first(fn (string $path) => file_exists($path));
        if ($font) {
            $white = imagecolorallocate($image, 255, 255, 255);
            $lines = explode("\n", wordwrap($title, 16, "\n", true));
            $lineHeight = 70;
            $top = (int) ($size / 2 - (count($lines) * $lineHeight) / 2 + 45);
            foreach ($lines as $index => $line) {
                $box = imagettfbbox(44, 0, $font, $line);
                $x = (int) (($size - ($box[2] - $box[0])) / 2);
                imagettftext($image, 44, 0, $x, $top + $index * $lineHeight, $white, $font, $line);
            }
        }

        $path = 'vitrinas/items/demo-' . Str::uuid() . '.webp';
        File::ensureDirectoryExists(public_path('storage/vitrinas/items'));
        imagewebp($image, public_path("storage/{$path}"), 80);
        imagedestroy($image);

        return $path;
    }

    private function vitrinas(): array
    {
        return [
            [
                'owner' => 'mebel_lux_navoiy', 'owner_name' => 'Rustam Mebel',
                'name' => 'Mebel Lux', 'type' => 'shop', 'city' => 'Navoiy', 'verified' => true, 'currency' => self::UZS,
                'description' => "Buyurtma asosida mebel: oshxona, shkaf-kupe, yotoqxona. O'lchab, yasab, o'rnatib beramiz. Kafolat 1 yil.",
                'address' => "Navoiy sh., Galaba shoh ko'chasi 12", 'phone' => '93-555-12-34', 'instagram' => 'mebel.lux',
                'work_hours' => '09:00–19:00',
                'sections' => [
                    'Oshxona mebeli' => [
                        ['Oshxona garnituri "Klassik"', "MDF fasad, 2.5 metr, moyka va plita joyi bilan.", 'from', 6500000, 'metr'],
                        ['Oshxona "Modern" oq glyanets', 'Tutqichsiz, yashirin yoritish bilan.', 'from', 8200000, 'metr'],
                        ['Oshxona burchagi', "Stol va 4 ta stul, yumshoq o'rindiq.", 'fixed', 3900000, null],
                    ],
                    'Shkaflar' => [
                        ['Shkaf-kupe 3 eshikli', "Oynali, 2.4 metr balandlik.", 'fixed', 4800000, null],
                        ["Kiyim shkafi 2 eshikli", "Bolalar xonasi uchun, rangini tanlaysiz.", 'fixed', 2300000, null],
                        ['Garderob xona', "O'lchov bo'yicha yasaladi.", 'negotiable', null, null],
                    ],
                    'Yotoqxona' => [
                        ['Yotoqxona to\'plami "Venera"', 'Krovat, 2 tumba, shkaf va trelyaj.', 'fixed', 12500000, null],
                        ['Ikki kishilik krovat', "Ko'tariladigan, ichida joy bor.", 'fixed', 3400000, null],
                    ],
                ],
            ],
            [
                'owner' => 'navoiy_osh_markazi', 'owner_name' => 'Osh Markazi',
                'name' => 'Navoiy Osh Markazi', 'type' => 'menu', 'city' => 'Navoiy', 'verified' => true, 'currency' => self::UZS,
                'description' => "Har kuni ertalab to'y oshi. Yetkazib berish shahar bo'ylab bepul (100 000 so'mdan).",
                'address' => 'Navoiy sh., Markaziy bozor yonida', 'phone' => '91-444-55-66', 'work_hours' => '07:00–15:00',
                'sections' => [
                    'Osh' => [
                        ["To'y oshi", "Devzira guruch, qazi, bedana tuxumi bilan.", 'fixed', 45000, 'porsiya'],
                        ['Choyxona palov', 'Mol go\'shti, sabzi, no\'xat.', 'fixed', 38000, 'porsiya'],
                        ["Osh 1 kg (olib ketish)", "Oila uchun.", 'fixed', 120000, 'kg'],
                    ],
                    'Salatlar' => [
                        ['Achchiq-chuchuk', 'Pomidor, piyoz, rayhon.', 'fixed', 12000, null],
                        ['Suzma salat', "Suzma, bodring, ko'kat.", 'fixed', 15000, null],
                    ],
                    'Ichimliklar' => [
                        ['Ko\'k choy', 'Choynak.', 'fixed', 5000, null],
                        ['Kompot', 'Uy kompoti, 1 litr.', 'fixed', 12000, null],
                        ['Coca-Cola 1.5 l', null, 'fixed', 14000, null],
                    ],
                ],
            ],
            [
                'owner' => 'usta_akmal', 'owner_name' => 'Akmal Usta',
                'name' => "Akmal Usta — ta'mirlash", 'type' => 'portfolio', 'city' => 'Zarafshon', 'currency' => self::UZS,
                'description' => "Kvartira va hovlilarni kalit topshirish darajasida ta'mirlayman. 12 yillik tajriba, o'z jamoam bor.",
                'phone' => '90-777-88-99', 'work_hours' => 'Har kuni',
                'sections' => [
                    'Ishlarim' => [
                        ["3 xonali kvartira, to'liq ta'mir", "Elektr, santexnika, shpaklovka, laminat. Muddat: 45 kun.", 'none', null, null],
                        ['Hammom va hojatxona', "Kafel, yashirin bachok, dush kabina.", 'none', null, null],
                        ["Hovli fasadi", "Travertin bilan qoplash, 120 kv.m.", 'none', null, null],
                        ['Oshxona ta\'miri', "Fartuk kafel, natyajnoy ship, yoritish.", 'none', null, null],
                    ],
                    'Narxlar' => [
                        ['Kafel yotqizish', null, 'from', 60000, 'kv.m'],
                        ['Shpaklovka va bo\'yoq', null, 'from', 35000, 'kv.m'],
                        ['Laminat yotqizish', null, 'fixed', 25000, 'kv.m'],
                        ["Kalit topshirish ta'mir", "Material bilan yoki materialsiz.", 'negotiable', null, null],
                    ],
                ],
            ],
            [
                'owner' => 'uy_topar_rieltor', 'owner_name' => 'Uy Topar',
                'name' => 'Uy Topar — rieltor', 'type' => 'realty', 'city' => null, 'verified' => true, 'currency' => self::USD,
                'description' => "Uy, kvartira va yer oldi-sotdisi. Hujjatlarni rasmiylashtirishda yordam beramiz.",
                'phone' => '94-321-00-11', 'work_hours' => '09:00–18:00',
                'sections' => [
                    'Sotuvda' => [
                        ['3 xonali kvartira, 4-qavat', "78 kv.m, yevro ta'mir, mebeli bilan.", 'fixed', 42000, null],
                        ['Hovli 8 sotix', "5 xona, garaj, mevali bog'.", 'fixed', 75000, null],
                        ['2 xonali kvartira, yangi uy', "Qora suvoq, 56 kv.m.", 'fixed', 27000, null],
                        ['Yer uchastka 12 sotix', 'Asosiy yo\'l bo\'yida.', 'negotiable', null, null],
                    ],
                    'Ijara' => [
                        ['1 xonali kvartira ijaraga', "Mebel va texnika bilan.", 'fixed', 2200000, 'oy'],
                        ['Ofis 30 kv.m', "Markazda, 1-qavat.", 'fixed', 4500000, 'oy'],
                    ],
                ],
            ],
            [
                'owner' => 'shirin_tort_uchquduq', 'owner_name' => 'Nilufar',
                'name' => 'Shirin Tortlar', 'type' => 'shop', 'city' => 'Uchquduq', 'currency' => self::UZS,
                'description' => "Uyda tayyorlangan tortlar va shirinliklar. Buyurtma 1 kun oldin.",
                'phone' => '99-111-22-33', 'instagram' => 'shirin.tort.uz', 'work_hours' => '08:00–21:00',
                'sections' => [
                    'Tortlar' => [
                        ['Napoleon', 'Klassik, qaymoqli.', 'fixed', 180000, 'kg'],
                        ['Medovik', "Asal va smetanali krem.", 'fixed', 170000, 'kg'],
                        ["Bolalar tug'ilgan kun torti", 'Rasm va yozuv bilan bezatiladi.', 'from', 250000, 'kg'],
                    ],
                    'Shirinliklar' => [
                        ['Ekler', '10 dona qutida.', 'fixed', 60000, null],
                        ['Chak-chak', '1 kg.', 'fixed', 90000, null],
                    ],
                ],
            ],
            [
                'owner' => 'avto_servis_qiziltepa', 'owner_name' => 'Avto Servis',
                'name' => 'Qiziltepa Avto Servis', 'type' => 'pricelist', 'city' => 'Qiziltepa', 'currency' => self::UZS,
                'description' => "Diagnostika, motor va xodovoy ta'miri, moy almashtirish. Damas, Cobalt, Gentra, Nexia.",
                'address' => 'Qiziltepa, Buxoro yo\'li 5-km', 'phone' => '97-600-70-80', 'work_hours' => '08:00–20:00',
                'sections' => [
                    'Xizmatlar' => [
                        ['Kompyuter diagnostika', null, 'fixed', 100000, null],
                        ['Moy almashtirish', 'Moy narxi alohida.', 'fixed', 40000, null],
                        ['Xodovoy tekshiruv', null, 'fixed', 50000, null],
                        ['Motor kapital ta\'mir', 'Ehtiyot qismlarsiz.', 'from', 2500000, null],
                        ['Razval-shoxojdenie', null, 'fixed', 120000, null],
                    ],
                ],
            ],
            [
                // Admin tasdiqlashini kutmoqda: katalogda ko'rinmaydi
                'owner' => 'yangi_sartarosh', 'owner_name' => 'Sardor Barber',
                'name' => 'Barber Sardor', 'type' => 'pricelist', 'city' => 'Navoiy', 'currency' => self::UZS,
                'status' => Vitrina::PENDING,
                'description' => "Erkaklar sartaroshxonasi. Navbatsiz, oldindan yozilish mumkin.",
                'phone' => '88-123-45-67', 'work_hours' => '10:00–21:00',
                'sections' => [
                    'Narxlar' => [
                        ['Soch olish', null, 'fixed', 50000, null],
                        ['Soqol', null, 'fixed', 30000, null],
                        ['Bolalar soch olishi', null, 'fixed', 35000, null],
                    ],
                ],
            ],
        ];
    }
}
