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
                ['Xonalar soni', false],
                ['Maydoni', false],
                ['Qavati', false],
            ],

            // ================= UY-JOY: HOVLI =================
            'Hovli' => [
                ['Xonalar soni', false],
                ['Yer maydoni', false],
                ["Ta'mir turi", false],
            ],

            // ================= UY-JOY: YER =================
            'Yer' => [
                ['Yer maydoni', false],
                ['Yer maqsadi', false],
            ],

            // ================= UY-JOY: DO'KON-OFIS =================
            "Do'kon-Ofis" => [
                ['Maydoni', false],
                ['Bino turi', false],
            ],

            // ================= TEXNIKA: TELEFON =================
            'Telefon' => [
                ['Telefon markasi', false],
                ['Doimiy xotira (ROM)', false],
                ['Telefon holati', false],
            ],

            // ================= TEXNIKA: NOUTBUK-KOMPYUTER =================
            'Noutbuk-Kompyuter' => [
                ['Kompyuter turi', false],
                ['Protsessor', false],
                ['Kompyuter RAM', false],
            ],

            // ================= TEXNIKA: MAISHIY TEXNIKA =================
            'Maishiy texnika' => [
                ['Maishiy texnika turi', false],
                ['Texnika holati', false],
            ],

            // ================= TEXNIKA: TV-AUDIO =================
            'TV-Audio' => [
                ['TV-Audio turi', false],
                ['Texnika holati', false],
            ],

            // ================= AVTO: YENGIL AVTO =================
            'Yengil avto' => [
                ['Ishlab chiqarilgan yili', false],
                ['Yurgan masofasi', false],
                ['Uzatish qutisi (Korobka)', false],
            ],

            // ================= AVTO: MOTOTSIKL =================
            'Mototsikl' => [
                ['Ishlab chiqarilgan yili', false],
                ['Mototsikl turi', false],
            ],

            // ================= AVTO: AVTOBUS-YUK TASHUVCHI =================
            'Avtobus-Yuk tashuvchi' => [
                ['Yuk mashinasi turi', false],
                ['Ishlab chiqarilgan yili', false],
            ],

            // ================= AVTO: EHTIYOT QISMLAR =================
            'Ehtiyot qismlar' => [
                ['Qism turi', false],
                ['Texnika holati', false],
            ],

            // ================= MEBEL =================
            'Divan-Krovat' => [
                ['Mebel holati', false],
                ['Mebel materiali', false],
            ],
            'Stol-Stul' => [
                ['Mebel holati', false],
                ['Mebel materiali', false],
            ],
            'Shkaf' => [
                ['Mebel holati', false],
                ['Mebel materiali', false],
            ],
            'Oshxona mebeli' => [
                ['Mebel holati', false],
                ['Mebel materiali', false],
            ],

            // ================= ISH =================
            'Doimiy ish' => [
                ['Bandlik turi', false],
                ['Ish tajribasi', false],
            ],
            'Kunlik ish' => [
                ['Bandlik turi', false],
                ["Maosh to'lash davriyligi", false],
            ],
            'Masofaviy ish' => [
                ['Bandlik turi', false],
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
            ],
            'Usta xizmatlari' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
            ],
            'Avto xizmatlar' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
            ],
            'IT va texnik xizmatlar' => [
                ["Xizmat ko'rsatish joyi", false],
                ['Xizmat tajribasi', false],
            ],

            // ================= TA'LIM =================
            'Kurslar' => [
                ['Dars formati', false],
                ['Dars darajasi', false],
            ],
            'Repetitor' => [
                ['Dars formati', false],
                ['Dars darajasi', false],
            ],
            'Online darslar' => [
                ['Dars formati', false],
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
                ['Kim uchun', false],
                ["Kiyim o'lchami", false],
                ['Buyum holati', false],
            ],
            'Poyabzal' => [
                ['Kim uchun', false],
                ["Poyabzal o'lchami", false],
                ['Buyum holati', false],
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
