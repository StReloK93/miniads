<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
class CategorySeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{

		// 	🏠 Uy-joy

		// 📱 Texnika

		// 🚗 Avto

		// 👕 Shaxsiy buyumlar

		// 💼 Ish va daromad

		// 🛠 Xizmatlar

		// 🪑 Mebel

		// 🎓 Ta’lim

		// 🐄 Chorva va hayvonlar

		// 🎁 Bepul beriladi

		// 🔎 Topildi / Yo‘qotildi

		// 📦 Boshqa

		// <Megaphone stroke-width="1.5" v-if="category.id == 52" class="size-5 text-blue-800" />


		$categories = [

			// 🏠 Uy-joy
			[
				'name' => 'Uy-joy',
				'image' => 'House',
				'subs' => [
					[
						'name' => 'Sotuv',
						'subs' => [
							['name' => 'Kvartira', 'is_page' => true, 'with_image' => true, 'with_price' => true],
							['name' => 'Hovli', 'is_page' => true, 'with_image' => true, 'with_price' => true],
							['name' => 'Yer', 'is_page' => true, 'with_image' => true, 'with_price' => true],
							['name' => "Do'kon-Ofis", 'is_page' => true, 'with_image' => true, 'with_price' => true],
						],
					],
					[
						'name' => 'Ijara',
						'subs' => [
							['name' => 'Kvartira', 'is_page' => true, 'with_image' => true, 'with_price' => true],
							['name' => 'Hovli', 'is_page' => true, 'with_image' => true, 'with_price' => true],
							['name' => 'Yer', 'is_page' => true, 'with_image' => true, 'with_price' => true],
							['name' => "Do'kon-Ofis", 'is_page' => true, 'with_image' => true, 'with_price' => true],
						],
					],
				]
			],

			// 📱 Texnika
			[
				'name' => 'Texnika',
				'image' => 'MonitorSmartphone',
				'subs' => [
					['name' => 'Telefon', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Noutbuk-Kompyuter', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Maishiy texnika', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'TV-Audio', 'is_page' => true, 'with_image' => true, 'with_price' => true],
				]
			],

			// 🚗 Avto
			[
				'name' => 'Avto',
				'image' => 'Car',
				'subs' => [
					['name' => 'Yengil avto', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Mototsikl', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Avtobus-Yuk tashuvchi', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Ehtiyot qismlar', 'is_page' => true, 'with_image' => true, 'with_price' => true],
				],
			],



			// 💼 Ish
			[
				'name' => 'Ish',
				'image' => 'BriefcaseBusiness',
				'subs' => [
					['name' => 'Doimiy ish', 'is_page' => true, 'with_image' => false, 'with_price' => false],
					['name' => 'Kunlik ish', 'is_page' => true, 'with_image' => false, 'with_price' => false],
					['name' => 'Masofaviy ish', 'is_page' => true, 'with_image' => false, 'with_price' => false],
					['name' => 'Xizmat takliflari', 'is_page' => true, 'with_image' => false, 'with_price' => false],
				]
			],

			// 🛠 Xizmatlar
			[
				'name' => 'Xizmatlar',
				'image' => 'Wrench',
				'subs' => [
					['name' => "Qurilish va ta'mirlash", 'is_page' => true, 'with_image' => true, 'with_price' => false],
					['name' => 'Usta xizmatlari', 'is_page' => true, 'with_image' => true, 'with_price' => false],
					['name' => 'Avto xizmatlar', 'is_page' => true, 'with_image' => true, 'with_price' => false],
					['name' => 'IT va texnik xizmatlar', 'is_page' => true, 'with_image' => true, 'with_price' => false],
				]
			],

			// 🪑 Mebel
			[
				'name' => 'Mebel',
				'image' => 'Armchair',
				'subs' => [
					['name' => 'Divan-Krovat', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Stol-Stul', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Shkaf', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Oshxona mebeli', 'is_page' => true, 'with_image' => true, 'with_price' => true],
				]
			],

			// 🎓 Ta’lim
			[
				'name' => "Talim",
				'image' => 'GraduationCap',
				'subs' => [
					['name' => 'Kurslar', 'is_page' => true, 'with_image' => false, 'with_price' => true],
					['name' => 'Repetitor', 'is_page' => true, 'with_image' => false, 'with_price' => true],
					['name' => 'Online darslar', 'is_page' => true, 'with_image' => false, 'with_price' => true],
				]
			],

			// 🐄 Chorva va hayvonlar
			[
				'name' => 'Hayvonot olami',
				'image' => 'Cat',
				'subs' => [
					['name' => 'Qoramol', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => "Qo'y-Echki", 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Parrandalar', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Uy hayvonlari', 'is_page' => true, 'with_image' => true, 'with_price' => true],
				]
			],

			// 👕 Shaxsiy buyumlar
			[
				'name' => 'Shaxsiy buyumlar',
				'image' => 'Shirt',
				'subs' => [
					['name' => 'Kiyim-kechak', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Poyabzal', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Parfyumeriya va kosmetika', 'is_page' => true, 'with_image' => true, 'with_price' => true],
					['name' => 'Aksessuarlar', 'is_page' => true, 'with_image' => true, 'with_price' => true],
				]
			],

			// 🔎 Topildi / Yo‘qotildi
			[
				'name' => "Topilmalar",
				'image' => 'Megaphone',
				'subs' => [
					['name' => 'Topilgan', 'is_page' => true, 'with_image' => true, 'with_price' => false],
					['name' => "Yo'qotilgan", 'is_page' => true, 'with_image' => true, 'with_price' => false],
				]
			],
			// 🎁 Bepul beriladi
			[
				'name' => 'Bepul beriladi',
				'image' => 'Gift',
				'is_page' => true,
				'with_image' => true,
				'with_price' => false,
			],

			// 📦 Boshqa
			[
				'name' => 'Boshqa',
				'image' => 'BadgeQuestionMark',
				'is_page' => true,
				'with_image' => true,
				'with_price' => true,
			],
		];


		$this->createCategories($categories);


	}

	private function createCategories(array $categories, $parentId = null): void
	{
		foreach ($categories as $item) {

			$category = Category::create([
				'name' => $item['name'],
				'image' => $item['image'] ?? null,
				'is_page' => $item['is_page'] ?? false,
				'with_image' => $item['with_image'] ?? false,
				'with_price' => $item['with_price'] ?? false,
				'parent_id' => $parentId,
			]);

			// Agar ichki sub-kategoriyalar bo‘lsa — recursive chaqiramiz
			if (!empty($item['subs']) && is_array($item['subs'])) {
				$this->createCategories($item['subs'], $category->id);
			}
		}
	}
}
