<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\District;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Lokal sinov uchun ~120 ta e'lon: turli bo'limlar, shaharlar (bir qismi "butun viloyat"),
 * so'm/dollar narxlar, narxsizlar, turli sanalar va ko'rishlar.
 * Ishga tushirish: php artisan db:seed --class=DemoProductSeeder
 */
class DemoProductSeeder extends Seeder
{
    private const UZS = 1;
    private const USD = 2;

    public function run(): void
    {
        // Har safar bir xil taqsimot: natijani solishtirish oson
        mt_srand(2026);

        $sellers = collect([
            ['name' => 'Jasur Karimov', 'username' => 'demo_jasur'],
            ['name' => 'Dilnoza Rahimova', 'username' => 'demo_dilnoza'],
            ['name' => 'Sardor Toshev', 'username' => 'demo_sardor'],
            ['name' => 'Malika Yusupova', 'username' => 'demo_malika'],
            ['name' => 'Bekzod Aliyev', 'username' => 'demo_bekzod'],
        ])->map(fn (array $user) => User::firstOrCreate(['username' => $user['username']], [...$user, 'role' => 'user']));

        $districtIds = District::pluck('id')->all();
        $categoryIds = Category::where('is_page', true)->pluck('id')->flip();

        foreach ($this->ads() as $index => [$categoryId, $title, $description, $price, $currency]) {
            if (!isset($categoryIds[$categoryId])) {
                continue;
            }

            // Har 7-e'lon butun viloyatga; qolganlari shaharlar bo'yicha aralash
            $districtId = $index % 7 === 0 || !$districtIds ? null : $districtIds[mt_rand(0, count($districtIds) - 1)];

            $product = Product::create([
                'title' => $title,
                'description' => $description,
                'category_id' => $categoryId,
                'user_id' => $sellers[$index % $sellers->count()]->id,
                'district_id' => $districtId,
                'phone' => '+99890' . mt_rand(1000000, 9999999),
                'price' => $price,
                'price_type_id' => $price ? $currency : null,
                'back_color_id' => mt_rand(1, 6),
                'views_count' => mt_rand(0, 400),
                'expires_at' => now()->addDays(mt_rand(5, 30)),
            ]);

            // Lentada turli vaqtlar: so'nggi 10 kun ichida
            $product->forceFill(['published_at' => now()->subMinutes(mt_rand(5, 60 * 24 * 10))])->saveQuietly();
        }
    }

    /** @return array<int, array{0: int, 1: string, 2: string, 3: ?int, 4: int}> */
    private function ads(): array
    {
        return [
            // Uy-joy: sotuv
            [3, '2 xonali kvartira, 3-qavat', "Ta'mirlangan, mebeli bilan. Hujjatlari tayyor.", 32000, self::USD],
            [3, '3 xonali kvartira sotiladi', "Markazda, 72 kv.m, yevro ta'mir, gaz va suv doimiy.", 45000, self::USD],
            [3, '1 xonali kvartira arzon', 'Yangi uy, 5-qavat, lift bor.', 19500, self::USD],
            [3, '4 xonali kvartira', 'Katta oila uchun qulay, 2 ta balkon, yerto\'la.', 58000, self::USD],
            [4, 'Hovli sotiladi 6 sotix', "4 xona, oshxona, hammom. Bog'ida mevali daraxtlar.", 65000, self::USD],
            [4, 'Yangi qurilgan hovli', "Pishiq g'ishtdan, 8 sotix, garaj bor.", 82000, self::USD],
            [4, 'Eski hovli arzon narxda', "Joyi yaxshi, maktab va bozor yaqin.", 27000, self::USD],
            [5, 'Yer uchastka 10 sotix', 'Qurilish uchun, svet va gaz yonida.', 12000, self::USD],
            [5, 'Dala hovli uchun yer', 'Shahardan 5 km, suv bor.', 6500, self::USD],
            [6, "Do'kon sotiladi 40 kv.m", "Asosiy ko'cha bo'yida, savdo uchun tayyor.", 38000, self::USD],
            [6, 'Ofis xonasi sotiladi', "Biznes markazda, 2-qavat, ta'mirlangan.", 25000, self::USD],

            // Uy-joy: ijara
            [8, '2 xonali kvartira ijaraga', 'Oilaga beriladi, mebel va texnika bilan.', 3000000, self::UZS],
            [8, 'Talabalarga kvartira ijaraga', "Universitet yaqinida, 4 kishigacha.", 2500000, self::UZS],
            [8, '1 xonali kvartira ijara', "Uzoq muddatga, oldindan 2 oylik to'lov.", 2000000, self::UZS],
            [8, 'Kunlik kvartira ijaraga', 'Toza, konditsioner, Wi-Fi bor.', 250000, self::UZS],
            [9, 'Hovli ijaraga beriladi', '3 xona, alohida kirish joyi.', 3500000, self::UZS],
            [9, "Hovlining yarmi ijaraga", 'Kichik oilaga, kommunal alohida.', 1800000, self::UZS],
            [11, 'Ofis ijaraga 25 kv.m', "Markazda, internet va mebel bor.", 4000000, self::UZS],
            [11, "Do'kon joyi ijaraga", "Bozor yonida, savdo uchun qulay.", 5000000, self::UZS],

            // Texnika
            [13, 'iPhone 13 128GB', "Ideal holatda, batareya 89%, karobka bor.", 5200000, self::UZS],
            [13, 'iPhone 11 sotiladi', "Kichik chizig'i bor, hammasi ishlaydi.", 3300000, self::UZS],
            [13, 'Samsung Galaxy A54', 'Yangi, 8/256, kafolati bor.', 4100000, self::UZS],
            [13, 'Redmi Note 12 arzon', "Bir yil ishlatilgan, zaryadkasi bilan.", 1700000, self::UZS],
            [13, 'Samsung S21 Ultra', "Ekranda kichik dog' bor, kamera zo'r.", 4500000, self::UZS],
            [13, 'Tugmali telefon Nokia', 'Keksalar uchun, batareyasi uzoq turadi.', 250000, self::UZS],
            [14, 'Noutbuk HP i5', '8GB RAM, SSD 256, ofis ishlari uchun.', 4200000, self::UZS],
            [14, 'MacBook Air M1', "Holati a'lo, 8/256, zaryadkasi bor.", 650, self::USD],
            [14, "O'yin kompyuteri RTX 3060", 'Ryzen 5, 16GB, monitor bilan birga.', 900, self::USD],
            [14, 'Lenovo noutbuk talabalar uchun', "Yengil, batareya yaxshi ushlaydi.", 3000000, self::UZS],
            [15, 'Muzlatkich Artel', "2 kamerali, ishlashi yaxshi.", 2800000, self::UZS],
            [15, 'Kir yuvish mashinasi Samsung', '7 kg, avtomat, yangidek.', 3600000, self::UZS],
            [15, "Mikroto'lqinli pech", 'LG, kam ishlatilgan.', 700000, self::UZS],
            [15, 'Konditsioner 12 talik', "O'rnatib beramiz, kafolat bilan.", 3900000, self::UZS],
            [15, 'Changyutgich arzon', 'Ishlaydi, shlangi yangi.', 400000, self::UZS],
            [16, 'Televizor Samsung 55 dyuym', 'Smart TV, 4K, pulti bilan.', 5500000, self::UZS],
            [16, 'Kolonka JBL', 'Bluetooth, ovozi baland.', 900000, self::UZS],
            [16, 'Eski televizor bepulga yaqin', 'Ishlaydi, faqat olib ketish kerak.', 150000, self::UZS],

            // Avto
            [18, 'Cobalt 2022, oq rang', 'Probeg 35 000 km, avtomat, toza.', 12500, self::USD],
            [18, 'Gentra 2020', "Mexanika, bir qo'ldan, kraska yo'q.", 11000, self::USD],
            [18, 'Nexia 3 sotiladi', "2018-yil, gaz-benzin, holati yaxshi.", 8200, self::USD],
            [18, 'Spark 2021', "Ayol kishi haydagan, garajda turgan.", 8800, self::USD],
            [18, 'Malibu 2 Turbo', "2019-yil, to'liq komplektatsiya.", 21500, self::USD],
            [18, 'Damas 2017', "Ishga tayyor, motor yaxshi.", 5600, self::USD],
            [18, 'Lacetti 2014 arzon', "Kuzov toza, motor kapital qilingan.", 6900, self::USD],
            [19, 'Mototsikl Honda', "125 kub, yangidek, hujjatlari joyida.", 1500, self::USD],
            [19, 'Skuter arzon', 'Shahar ichida yurishga qulay.', 6000000, self::UZS],
            [20, 'Isuzu yuk mashinasi', "5 tonna, ishga tayyor.", 24000, self::USD],
            [20, 'Labo sotiladi', "2020-yil, tentli.", 6500, self::USD],
            [21, 'Cobalt uchun disklar', '15 radius, 4 dona.', 2200000, self::UZS],
            [21, 'Akkumulyator 60 Ah', 'Yangi, kafolat 1 yil.', 900000, self::UZS],
            [21, 'Nexia ehtiyot qismlari', "Fara, bamper, eshik — hammasi bor.", null, self::UZS],

            // Ish
            [23, 'Hamshira kerak', "Xususiy klinikaga tajribali hamshira kerak. Ish vaqti 8:00–17:00.", 4500000, self::UZS],
            [23, 'Sotuvchi kerak', "Kiyim do'koniga qiz bola sotuvchi kerak.", 3000000, self::UZS],
            [23, 'Haydovchi ishga olinadi', "B, C toifali guvohnoma, tajriba 3 yildan.", 5000000, self::UZS],
            [23, 'Buxgalter kerak', '1C dasturini biladigan, tajribali.', 6000000, self::UZS],
            [23, 'Oshpaz kerak kafega', "Milliy taomlarni pishira oladigan.", 5500000, self::UZS],
            [23, 'Qorovul ishga olinadi', "Smenali ish, tunda.", 2800000, self::UZS],
            [23, "O'qituvchi kerak", "Xususiy maktabga ingliz tili o'qituvchisi.", 7000000, self::UZS],
            [24, 'Kunlik ishchilar kerak', "Qurilishga yordamchi, kunlik to'lov.", 200000, self::UZS],
            [24, 'Yuk tushiruvchi kerak', 'Ombor ishi, kunlik haq.', 180000, self::UZS],
            [24, "Bog' tozalashga odam kerak", 'Bir kunlik ish.', 150000, self::UZS],
            [25, 'Masofaviy operator', "Uydan turib qo'ng'iroqlarga javob berish.", 3500000, self::UZS],
            [25, 'SMM mutaxassis kerak', 'Instagram sahifa yuritish, masofaviy.', 4000000, self::UZS],
            [26, "Hamshiralik xizmati uyga", "Ukol, kapelnitsa qo'yaman. Tajriba 10 yil.", 50000, self::UZS],
            [26, 'Enaga xizmati', "Bolalarga qarayman, tajribam bor.", null, self::UZS],
            [26, 'Haydovchi xizmati', "O'z mashinam bilan yuk tashiyman.", null, self::UZS],

            // Xizmatlar
            [28, "Uy ta'mirlash", "Shpaklovka, bo'yoq, kafel — sifatli va tez.", null, self::UZS],
            [28, 'Kafel yotqizish', 'Kv.m narxi kelishiladi.', 60000, self::UZS],
            [28, 'Tom yopish xizmati', "Profnastil, shifer — kafolat bilan.", null, self::UZS],
            [29, 'Santexnik xizmati', "Quvur, kran, unitaz o'rnatish 24/7.", null, self::UZS],
            [29, 'Elektrik ustasi', "Uy va ofislarda elektr ishlari.", 100000, self::UZS],
            [29, "Muzlatkich ta'mirlash", 'Uyga chiqib tuzatamiz.', null, self::UZS],
            [30, 'Avto elektrik', "Diagnostika va ta'mirlash.", null, self::UZS],
            [30, 'Shinomontaj 24 soat', "G'ildirak almashtirish, balansirovka.", 50000, self::UZS],
            [31, 'Kompyuter ustasi', "Windows o'rnatish, viruslarni tozalash.", 100000, self::UZS],
            [31, 'Sayt yaratib beramiz', 'Biznes uchun sayt va Telegram bot.', 300, self::USD],
            [58, 'Uy tozalash xizmati', "Ta'mirdan keyin tozalash, gilam yuvish.", null, self::UZS],
            [58, 'Gilam yuvish', 'Olib ketib, yetkazib beramiz.', 15000, self::UZS],

            // Mebel
            [33, 'Divan sotiladi', "Burchakli, ochiladigan, holati yaxshi.", 2500000, self::UZS],
            [33, 'Ikki kishilik krovat', 'Matras bilan, yangidek.', 3000000, self::UZS],
            [33, 'Bolalar krovati', "Yog'ochdan, chiroyli.", 900000, self::UZS],
            [34, 'Stol-stul komplekti', '6 ta stul bilan, oshxona uchun.', 2200000, self::UZS],
            [34, 'Ofis stoli', 'Tortmali, kompyuter uchun.', 800000, self::UZS],
            [35, 'Shkaf-kupe', "Oynali, 3 eshikli.", 3500000, self::UZS],
            [35, 'Kiyim shkafi arzon', "Ko'chish sababli sotiladi.", 700000, self::UZS],
            [36, 'Oshxona mebeli buyurtmaga', "O'lchab, yasab, o'rnatib beramiz.", null, self::UZS],
            [36, 'Oshxona garnituri', '2.5 metr, moyka bilan.', 4000000, self::UZS],

            // Ta'lim
            [38, 'Ingliz tili kurslari', "IELTS ga tayyorlov, guruh va individual.", 400000, self::UZS],
            [38, 'Kompyuter savodxonligi kursi', "Word, Excel, internet — 1 oy.", 300000, self::UZS],
            [38, 'Dasturlash kursi', "Noldan Python, amaliy loyihalar bilan.", 600000, self::UZS],
            [39, 'Matematika repetitori', "Abituriyentlarga, test va misollar.", 350000, self::UZS],
            [39, 'Rus tili repetitor', "Bolalar va kattalar uchun.", 300000, self::UZS],
            [40, 'Online ingliz tili', 'Zoom orqali darslar, haftasiga 3 marta.', 250000, self::UZS],

            // Hayvonot olami
            [42, 'Sigir sotiladi', "Sog'in sigir, kuniga 12 litr sut beradi.", 12000000, self::UZS],
            [42, 'Buqacha', '8 oylik, semiz.', 7000000, self::UZS],
            [43, "Qo'y sotiladi", "Hisori zoti, 5 bosh.", 2500000, self::UZS],
            [43, 'Echki', "Sog'in echki, uloqchasi bilan.", 1500000, self::UZS],
            [44, 'Tovuqlar sotiladi', "Tuxum qiladigan, 20 dona.", 70000, self::UZS],
            [44, "Bedana va tuxumi", "Ko'p miqdorda.", 1500, self::UZS],
            [45, 'Mushukcha bepul', "Uy mushugi, tarbiyali.", null, self::UZS],
            [45, 'Kuchukcha sotiladi', "Ovcharka, 2 oylik, emlangan.", 1500000, self::UZS],

            // Shaxsiy buyumlar
            [47, 'Ayollar paltosi', "46 o'lcham, bir marta kiyilgan.", 600000, self::UZS],
            [47, "Erkaklar kostyumi", "To'y uchun, 50 o'lcham.", 900000, self::UZS],
            [47, 'Bolalar kiyimlari', "1-3 yoshga, hammasi bir joyda.", 200000, self::UZS],
            [48, 'Krossovka Nike', "42 o'lcham, original.", 700000, self::UZS],
            [48, 'Ayollar tuflisi', "38 o'lcham, yangi.", 350000, self::UZS],
            [49, 'Atir original', 'Dior Sauvage, 100 ml.', 1200000, self::UZS],
            [50, 'Qo\'l soati Casio', 'Original, karobka bilan.', 800000, self::UZS],
            [50, 'Ayollar sumkasi', "Charm, qora rang.", 450000, self::UZS],

            // Topilmalar
            [52, 'Pasport topildi', "Bozor yonidan topildi, egasi qo'ng'iroq qilsin.", null, self::UZS],
            [52, 'Kalitlar topildi', "Avtobus bekatida topildi.", null, self::UZS],
            [53, "Telefon yo'qotildi", "Qora iPhone, topganga suyunchi beriladi.", null, self::UZS],
            [53, "Hujjatlar yo'qolgan", "Haydovchilik guvohnomasi va texpasport.", null, self::UZS],

            // Boshqalar
            [54, 'Eski mebel bepul beriladi', "O'zingiz olib ketasiz.", null, self::UZS],
            [54, 'Kitoblar bepul', "Maktab darsliklari, 5-9 sinf.", null, self::UZS],
            [55, "Sport anjomlari", "Gantel, turnik, yugurish yo'lakchasi.", 1500000, self::UZS],
            [55, 'Velosiped', "Bolalar uchun, 20 radius.", 600000, self::UZS],
            [56, 'Kafe ochildi', "Milliy taomlar, yetkazib berish bor.", null, self::UZS],
            [56, 'Osh markazi', "Har kuni ertalab to'y oshi.", null, self::UZS],
            [57, "Naqdga plastik o'tkazaman", "Tez va ishonchli, foizi kam.", null, self::UZS],
            [59, "Navoiyga yo'lovchi olaman", "Ertalab 7:00 da chiqaman, 3 ta joy bor.", null, self::UZS],
            [59, "Toshkentga yo'lovchi", "Kechqurun jo'nayman, Cobalt.", null, self::UZS],
        ];
    }
}
