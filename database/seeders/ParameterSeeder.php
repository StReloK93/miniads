<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parameter;
use Illuminate\Support\Facades\Schema;

class ParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Parameter::truncate();
        Schema::enableForeignKeyConstraints();

        $parameters = [
            // ==================== UY-JOY ====================
            [
                'title' => 'Xonalar soni',
                'placeholder' => 'Masalan: 3',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'xona',
                'options' => null
            ],
            [
                'title' => 'Maydoni',
                'placeholder' => 'Masalan: 65',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'm²',
                'options' => null
            ],
            [
                'title' => 'Qavati',
                'placeholder' => 'Masalan: 4',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => '-qavat',
                'options' => null
            ],
            [
                'title' => 'Umumiy qavatlar',
                'placeholder' => 'Masalan: 9',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'qavatli',
                'options' => null
            ],
            [
                'title' => "Ta'mir turi",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yevroremont', "Yaxshi ta'mirlangan", "O'rtacha", "Ta'mirsiz (Korobka)"]
            ],
            [
                'title' => 'Jihozlanganligi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["To'liq jihozlangan (Mebel + Texnika)", "Qisman jihozlangan", "Jihozlarsiz"]
            ],
            [
                'title' => 'Yer maydoni',
                'placeholder' => 'Masalan: 6',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'sotix',
                'options' => null
            ],
            [
                'title' => 'Yer maqsadi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["Turar joy qurish uchun", "Qishloq xo'jaligi", "Tijorat / Ishlab chiqarish"]
            ],
            [
                'title' => 'Bino turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["Biznes markaz / Savdo markazi", "Alohida bino", "Ko'p qavatli uyning 1-qavati"]
            ],

            // ==================== AVTOMOBILLAR VA TRANSPORT ====================
            [
                'title' => 'Ishlab chiqarilgan yili',
                'placeholder' => 'Masalan: 2022',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'yil',
                'options' => null
            ],
            [
                'title' => 'Yurgan masofasi',
                'placeholder' => 'Masalan: 45 000',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'km',
                'options' => null
            ],
            [
                'title' => 'Kuzov turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Sedan', 'Xetchbek', 'Krossover / SUV', 'Universal', 'Kupe', 'Pikapp', 'Miniven']
            ],
            [
                'title' => 'Uzatish qutisi (Korobka)',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Mexanika', 'Avtomat', 'Variator', 'Robot']
            ],
            [
                'title' => "Yoqilg'i turi",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Benzin', 'Gaz (Metan)', 'Gaz (Propan)', 'Dizel', 'Gibrid', 'Elektr']
            ],
            [
                'title' => 'Avto holati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["A'lo", 'Yaxshi', "O'rtacha", "Ta'mir talab"]
            ],
            [
                'title' => 'Avto rangi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Oq', 'Qora', 'Kulrang (Mokriy asfalt)', 'Kumushrang', "Ko'k", 'Qizil', 'Boshqa']
            ],
            [
                'title' => 'Mototsikl turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Skuter', 'Moped', 'Sportbayk', "Klassik / Yo'l", 'Kross / Enduro', 'Kvadrotsikl', 'Elektroskuter']
            ],
            [
                'title' => 'Dvigatel hajmi',
                'placeholder' => 'Masalan: 150',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'sm³',
                'options' => null
            ],
            [
                'title' => 'Yuk mashinasi turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yuk mashinasi (Fura)', 'Samosval', 'Gazel / Bortli', 'Mikroavtobus', 'Avtobus', 'Maxsus texnika']
            ],
            [
                'title' => "Yuk ko'tarish quvvati",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['1.5 tonnagacha', '1.5 - 3 tonna', '3 - 5 tonna', '5 - 10 tonna', "10 tonnadan ortiq"]
            ],
            [
                'title' => 'Qism turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Kuzov qismlari', 'Dvigatel va transmissiya', 'Xodovoy qismlar', 'Shinalar va disklar', 'Elektronika va optika', 'Aksessuarlar', 'Moy va suyuqliklar']
            ],

            // ==================== TEXNIKA (TELEFON, KOMPYUTER VA BOSHQALAR) ====================
            [
                'title' => 'Telefon markasi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Apple (iPhone)', 'Samsung', 'Xiaomi (Redmi/Poco)', 'Honor', 'Huawei', 'Vivo', 'Realme', 'Infinix', 'Tecno', 'Boshqa']
            ],
            [
                'title' => 'Doimiy xotira (ROM)',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['32 GB', '64 GB', '128 GB', '256 GB', '512 GB', '1 TB']
            ],
            [
                'title' => 'Tezkor xotira (RAM)',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['3 GB', '4 GB', '6 GB', '8 GB', '12 GB', '16 GB', '24 GB']
            ],
            [
                'title' => 'Telefon holati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yangi (Qutida)', 'Ideal (Yangi kabi)', 'Yaxshi', "Ta'mir talab"]
            ],
            [
                'title' => 'Telefon rangi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Qora', 'Oq / Kumush', "Ko'k", 'Kulrang', 'Tilla (Gold)', 'Binafsha', 'Yashil', 'Boshqa']
            ],
            [
                'title' => 'Kompyuter turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Noutbuk', 'Stol kompyuteri (PC)', 'Monoblok (All-in-One)', 'Netbuk']
            ],
            [
                'title' => 'Protsessor',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9', 'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'Apple M1 / M2 / M3', 'Boshqa']
            ],
            [
                'title' => 'Kompyuter RAM',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['4 GB', '8 GB', '16 GB', '32 GB', '64 GB']
            ],
            [
                'title' => 'Xotira turi va hajmi (SSD/HDD)',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['128 GB SSD', '256 GB SSD', '512 GB SSD', '1 TB SSD', '1 TB HDD', 'SSD + HDD']
            ],
            [
                'title' => 'Videokarta',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Integratsiyalashgan (Intel/AMD/Apple)', 'NVIDIA GeForce GTX', 'NVIDIA GeForce RTX (O‘yin uchun)', 'AMD Radeon']
            ],
            [
                'title' => 'Kompyuter holati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yangi', "A'lo (Ideal)", "O'rtacha", "Ta'mir talab"]
            ],
            [
                'title' => 'Maishiy texnika turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Muzlatgich', 'Kir yuvish mashinasi', 'Konditsioner', 'Gaz plita / Duxovka', 'Changyutgich', "Mikroto'lqinli pech", 'Idish yuvish mashinasi', 'Boshqa']
            ],
            [
                'title' => 'TV-Audio turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Televizor / Smart TV', 'Akustika / Kolonka', 'Quloqchinlar', 'Musiqiy markaz', 'Proyektor']
            ],
            [
                'title' => 'Ekran diagonali',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['32 dyuymgacha', '40 - 43 dyuym', '50 - 55 dyuym', "65 dyuym va undan katta"]
            ],
            [
                'title' => 'Texnika holati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yangi', "Ishlatilgan (a'lo)", 'Yaxshi', "Ta'mir talab"]
            ],

            // ==================== MEBEL ====================
            [
                'title' => 'Mebel materiali',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['MDF', 'LDSP', "Tabiiy yog'och", 'Metall', 'Mato / Koja', 'Boshqa']
            ],
            [
                'title' => 'Mebel holati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yangi (Buyurtmaga)', 'Yangi (Tayyor)', "Ishlatilgan (a'lo)", "Ishlatilgan (o'rtacha)"]
            ],
            [
                'title' => 'Mebel rangi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["Jigarrang (Yog'och)", 'Oq', 'Kulrang', 'Qora', 'Bej', 'Boshqa']
            ],

            // ==================== ISH VA DAROMAD ====================
            [
                'title' => 'Bandlik turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["To'liq stavka (Full-time)", 'Yarim stavka (Part-time)', 'Smenali grafik', 'Erkin grafik', 'Masofaviy (Remote)']
            ],
            [
                'title' => 'Ish tajribasi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Tajribasiz', '1 yildan 3 yilgacha', '3 yildan 5 yilgacha', "5 yildan ortiq"]
            ],
            [
                'title' => "Maosh to'lash davriyligi",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Oylik', 'Haftalik', 'Kunlik', 'Kelishuv asosida']
            ],

            // ==================== XIZMATLAR ====================
            [
                'title' => "Xizmat ko'rsatish joyi",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Mijoz manziliga borib', "O'z ustaxonasida / Ofisda", 'Masofaviy (Online)']
            ],
            [
                'title' => 'Xizmat tajribasi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['1-3 yil', '3-5 yil', "5 yildan ortiq mutaxassis"]
            ],
            [
                'title' => "To'lov shakli",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Naqd pul', 'Karta orqali (Click/Payme)', 'Hisob raqamga (Perechisleniye)', 'Kelishilgan holda']
            ],

            // ==================== TA'LIM ====================
            [
                'title' => 'Dars formati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Individual (Yakka tartibda)', 'Kichik guruhda', 'Online (Zoom / Telegram)', "Oflayn (O'quv markazda)"]
            ],
            [
                'title' => 'Dars darajasi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ["Boshlang'ich (Noldan)", "O'rta daraja", 'Murakkab / Sertifikatga tayyorgarlik']
            ],

            // ==================== HAYVONOT OLAMI ====================
            [
                'title' => 'Yoshi / Oyligi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Bola (Yosh)', '6-12 oylik', '1-2 yosh', '3 yosh va katta']
            ],
            [
                'title' => 'Zoti',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Mahalliy (Jaydari)', 'Gissar', 'Golshtin / Shvits', 'Angus', 'Zotdor / Toza qon']
            ],

            // ==================== SHAXSIY BUYUMLAR ====================
            [
                'title' => 'Kim uchun',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Erkaklar uchun', 'Ayollar uchun', 'Bolalar uchun', 'Uniseks']
            ],
            [
                'title' => "Kiyim o'lchami",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['XS (40-42)', 'S (42-44)', 'M (46-48)', 'L (50-52)', 'XL (54-56)', 'XXL (58+)', 'Standart']
            ],
            [
                'title' => "Poyabzal o'lchami",
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['35-37', '38-39', '40-41', '42-43', '44-45', '46+']
            ],
            [
                'title' => 'Buyum holati',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yangi (Teg bilan)', 'Yangi kabi', 'Yaxshi']
            ],
        ];

        foreach ($parameters as $parameter) {
            Parameter::create($parameter);
        }
    }
}
