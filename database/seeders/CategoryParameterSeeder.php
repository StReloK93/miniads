<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Parameter;
use App\Models\CategoryParameter;
use Illuminate\Support\Facades\Schema;

class CategoryParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        CategoryParameter::truncate();
        Schema::enableForeignKeyConstraints();

        $parametersByTitle = Parameter::all()->keyBy('title');

        $map = [
            // ================= UY-JOY: KVARTIRA =================
            'Kvartira' => [
                ['Xonalar soni', true],
                ['Maydoni', true],
                ['Qavati', false],
                ['Umumiy qavatlar', false],
                ["Ta'mir turi", false],
                ['Jihozlanganligi', false],
            ],

            // ================= UY-JOY: HOVLI =================
            'Hovli' => [
                ['Xonalar soni', true],
                ['Yer maydoni', true],
                ['Qavatlar soni', false],
                ["Ta'mir turi", false],
            ],

            // ================= UY-JOY: YER =================
            'Yer' => [
                ['Yer maydoni', true],
                ['Yer maqsadi', false],
            ],

            // ================= UY-JOY: DO'KON-OFIS =================
            "Do'kon-Ofis" => [
                ['Maydoni', true],
                ['Bino turi', false],
                ["Ta'mir turi", false],
            ],

            // ================= TEXNIKA: TELEFON =================
            'Telefon' => [
                ['Telefon markasi', true],
                ['Doimiy xotira (ROM)', true],
                ['Tezkor xotira (RAM)', false],
                ['Telefon holati', true],
                ['Telefon rangi', false],
            ],

            // ================= TEXNIKA: NOUTBUK-KOMPYUTER =================
            'Noutbuk-Kompyuter' => [
                ['Kompyuter turi', true],
                ['Protsessor', true],
                ['Kompyuter RAM', true],
                ['Xotira turi va hajmi (SSD/HDD)', false],
                ['Videokarta', false],
                ['Kompyuter holati', true],
            ],

            // ================= TEXNIKA: MAISHIY TEXNIKA =================
            'Maishiy texnika' => [
                ['Maishiy texnika turi', true],
                ['Texnika holati', true],
            ],

            // ================= TEXNIKA: TV-AUDIO =================
            'TV-Audio' => [
                ['TV-Audio turi', true],
                ['Ekran diagonali', false],
                ['Texnika holati', true],
            ],

            // ================= AVTO: YENGIL AVTO =================
            'Yengil avto' => [
                ['Ishlab chiqarilgan yili', true],
                ['Yurgan masofasi', true],
                ['Uzatish qutisi (Korobka)', true],
                ["Yoqilg'i turi", true],
                ['Kuzov turi', false],
                ['Avto holati', true],
                ['Avto rangi', false],
            ],

            // ================= AVTO: MOTOTSIKL =================
            'Mototsikl' => [
                ['Ishlab chiqarilgan yili', true],
                ['Yurgan masofasi', false],
                ['Mototsikl turi', true],
                ['Dvigatel hajmi', false],
            ],

            // ================= AVTO: AVTOBUS-YUK TASHUVCHI =================
            'Avtobus-Yuk tashuvchi' => [
                ['Yuk mashinasi turi', true],
                ['Ishlab chiqarilgan yili', true],
                ['Yurgan masofasi', false],
                ["Yuk ko'tarish quvvati", false],
            ],

            // ================= AVTO: EHTIYOT QISMLAR =================
            'Ehtiyot qismlar' => [
                ['Qism turi', true],
                ['Texnika holati', false],
            ],

            // ================= MEBEL =================
            'Divan-Krovat' => [
                ['Mebel materiali', false],
                ['Mebel holati', true],
                ['Mebel rangi', false],
            ],
            'Stol-Stul' => [
                ['Mebel materiali', false],
                ['Mebel holati', true],
                ['Mebel rangi', false],
            ],
            'Shkaf' => [
                ['Mebel materiali', false],
                ['Mebel holati', true],
                ['Mebel rangi', false],
            ],
            'Oshxona mebeli' => [
                ['Mebel materiali', false],
                ['Mebel holati', true],
                ['Mebel rangi', false],
            ],

            // ================= ISH =================
            'Doimiy ish' => [
                ['Bandlik turi', true],
                ['Ish tajribasi', false],
                ["Maosh to'lash davriyligi", false],
            ],
            'Kunlik ish' => [
                ['Bandlik turi', false],
                ["Maosh to'lash davriyligi", true],
            ],
            'Masofaviy ish' => [
                ['Bandlik turi', true],
                ['Ish tajribasi', false],
            ],
            'Xizmat takliflari' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
            ],

            // ================= XIZMATLAR =================
            "Qurilish va ta'mirlash" => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
                ["To'lov shakli", false],
            ],
            'Usta xizmatlari' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
                ["To'lov shakli", false],
            ],
            'Avto xizmatlar' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
                ["To'lov shakli", false],
            ],
            'IT va texnik xizmatlar' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
            ],

            // ================= TA'LIM =================
            'Kurslar' => [
                ['Dars formati', true],
                ['Dars darajasi', false],
            ],
            'Repetitor' => [
                ['Dars formati', true],
                ['Dars darajasi', false],
            ],
            'Online darslar' => [
                ['Dars formati', true],
                ['Dars darajasi', false],
            ],

            // ================= HAYVONOT OLAMI =================
            'Qoramol' => [
                ['Yoshi / Oyligi', false],
                ['Zoti', false],
            ],
            "Qo'y-Echki" => [
                ['Yoshi / Oyligi', false],
                ['Zoti', false],
            ],
            'Parrandalar' => [
                ['Yoshi / Oyligi', false],
                ['Zoti', false],
            ],
            'Uy hayvonlari' => [
                ['Yoshi / Oyligi', false],
                ['Zoti', false],
            ],

            // ================= SHAXSIY BUYUMLAR =================
            'Kiyim-kechak' => [
                ['Kim uchun', true],
                ["Kiyim o'lchami", true],
                ['Buyum holati', true],
            ],
            'Poyabzal' => [
                ['Kim uchun', true],
                ["Poyabzal o'lchami", true],
                ['Buyum holati', true],
            ],
            'Parfyumeriya va kosmetika' => [
                ['Kim uchun', false],
                ['Buyum holati', false],
            ],
            'Aksessuarlar' => [
                ['Kim uchun', false],
                ['Buyum holati', false],
            ],
        ];

        $insertData = [];

        foreach ($map as $categoryName => $paramList) {
            $categories = Category::where('name', $categoryName)->where('is_page', true)->get();

            foreach ($categories as $category) {
                $sortOrder = 1;
                foreach ($paramList as $item) {
                    [$paramTitle, $isRequired] = $item;

                    $param = $parametersByTitle->get($paramTitle);
                    if ($param) {
                        $insertData[] = [
                            'category_id' => $category->id,
                            'parameter_id' => $param->id,
                            'is_required' => $isRequired,
                            'sort_order' => $sortOrder++,
                        ];
                    }
                }
            }
        }

        if (!empty($insertData)) {
            CategoryParameter::insert($insertData);
        }
    }
}
