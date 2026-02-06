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
							['name' => 'Kvartira', 'is_page' => true],
							['name' => 'Hovli', 'is_page' => true],
							['name' => 'Yer', 'is_page' => true],
							['name' => "Do'kon-Ofis", 'is_page' => true],
						],
					],
					[
						'name' => 'Ijara',
						'subs' => [
							['name' => 'Kvartira', 'is_page' => true],
							['name' => 'Hovli', 'is_page' => true],
							['name' => 'Yer', 'is_page' => true],
							['name' => "Do'kon-Ofis", 'is_page' => true],
						],
					],
				]
			],

			// 📱 Texnika
			[
				'name' => 'Texnika',
				'image' => 'MonitorSmartphone',
				'subs' => [
					['name' => 'Telefon', 'is_page' => true],
					['name' => 'Noutbuk-Kompyuter', 'is_page' => true],
					['name' => 'Maishiy texnika', 'is_page' => true],
					['name' => 'TV-Audio', 'is_page' => true],
				]
			],

			// 🚗 Avto
			[
				'name' => 'Avto',
				'image' => 'Car',
				'subs' => [
					['name' => 'Yengil avto', 'is_page' => true],
					['name' => 'Mototsikl', 'is_page' => true],
					['name' => 'Avtobus-Yuk tashuvchi', 'is_page' => true],
					['name' => 'Ehtiyot qismlar', 'is_page' => true],
					['name' => 'Ijara', 'is_page' => true],
				],
			],



			// 💼 Ish
			[
				'name' => 'Ish',
				'image' => 'BriefcaseBusiness',
				'subs' => [
					['name' => 'Doimiy ish', 'is_page' => true],
					['name' => 'Kunlik ish', 'is_page' => true],
					['name' => 'Masofaviy ish', 'is_page' => true],
					['name' => 'Xizmat takliflari', 'is_page' => true],
				]
			],

			// 🛠 Xizmatlar
			[
				'name' => 'Xizmatlar',
				'image' => 'Wrench',
				'subs' => [
					['name' => "Qurilish va ta'mirlash", 'is_page' => true],
					['name' => 'Usta xizmatlari', 'is_page' => true],
					['name' => 'Avto xizmatlar', 'is_page' => true],
					['name' => 'IT va texnik xizmatlar', 'is_page' => true],
				]
			],

			// 🪑 Mebel
			[
				'name' => 'Mebel',
				'image' => 'Armchair',
				'subs' => [
					['name' => 'Divan-Krovat', 'is_page' => true],
					['name' => 'Stol-Stul', 'is_page' => true],
					['name' => 'Shkaf', 'is_page' => true],
					['name' => 'Oshxona mebeli', 'is_page' => true],
				]
			],

			// 🎓 Ta’lim
			[
				'name' => "Talim",
				'image' => 'GraduationCap',
				'subs' => [
					['name' => 'Kurslar', 'is_page' => true],
					['name' => 'Repetitor', 'is_page' => true],
					['name' => 'Online darslar', 'is_page' => true],
				]
			],

			// 🐄 Chorva va hayvonlar
			[
				'name' => 'Hayvonot olami',
				'image' => 'Cat',
				'subs' => [
					['name' => 'Qoramol', 'is_page' => true],
					['name' => "Qo'y-Echki", 'is_page' => true],
					['name' => 'Parrandalar', 'is_page' => true],
					['name' => 'Uy hayvonlari', 'is_page' => true],
				]
			],

			// 👕 Shaxsiy buyumlar
			[
				'name' => 'Shaxsiy buyumlar',
				'image' => 'Shirt',
				'subs' => [
					['name' => 'Kiyim-kechak', 'is_page' => true],
					['name' => 'Poyabzal', 'is_page' => true],
					['name' => 'Parfyumeriya va kosmetika', 'is_page' => true],
					['name' => 'Aksessuarlar', 'is_page' => true],
				]
			],

			// 🔎 Topildi / Yo‘qotildi
			[
				'name' => "Topilmalar",
				'image' => 'Megaphone',
				'subs' => [
					['name' => 'Topildi', 'is_page' => true],
					['name' => "Yo'qotildi", 'is_page' => true],
				]
			],
			// 🎁 Bepul beriladi
			[
				'name' => 'Bepul beriladi',
				'image' => 'Gift',
				'is_page' => true
			],

			// 📦 Boshqa
			[
				'name' => 'Boshqa',
				'image' => 'BadgeQuestionMark',
				'is_page' => true
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
				'parent_id' => $parentId,
			]);

			// Agar ichki sub-kategoriyalar bo‘lsa — recursive chaqiramiz
			if (!empty($item['subs']) && is_array($item['subs'])) {
				$this->createCategories($item['subs'], $category->id);
			}
		}
	}
}
