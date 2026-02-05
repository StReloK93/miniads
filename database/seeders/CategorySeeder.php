<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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




		$categories = [

			// 🏠 Uy-joy
			[
				'name' => 'Uy-joy',
				'image' => '/icons/house.svg',
				'subs' => [
					[
						'name' => 'Sotuv',
						'subs' => [
							['name' => 'Kvartira', 'is_page' => true],
							['name' => 'Hovli', 'is_page' => true],
							['name' => 'Yer', 'is_page' => true],
							['name' => "Do'kon / Ofis", 'is_page' => true],
						],
					],
					[
						'name' => 'Ijara',
						'subs' => [
							['name' => 'Kvartira', 'is_page' => true],
							['name' => 'Hovli', 'is_page' => true],
							['name' => 'Yer', 'is_page' => true],
							['name' => "Do'kon / Ofis", 'is_page' => true],
						],
					],
				]
			],

			// 📱 Texnika
			[
				'name' => 'Texnika',
				'image' => '/icons/tech.svg',
				'subs' => [
					['name' => 'Telefon', 'is_page' => true],
					['name' => 'Noutbuk / Kompyuter', 'is_page' => true],
					['name' => 'Maishiy texnika', 'is_page' => true],
					['name' => 'TV / Audio', 'is_page' => true],
				]
			],

			// 🚗 Avto
			[
				'name' => 'Avto',
				'image' => '/icons/car.svg',
				'subs' => [
					['name' => 'Yengil avto', 'is_page' => true],
					['name' => 'Mototsikl', 'is_page' => true],
					['name' => 'Avtobus / Yuk tashuvchi', 'is_page' => true],
					['name' => 'Ehtiyot qismlar', 'is_page' => true],
					['name' => 'Ijara', 'is_page' => true],
				],
			],



			// 💼 Ish
			[
				'name' => 'Ish',
				'image' => '/icons/job.svg',
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
				'image' => '/icons/service.svg',
				'subs' => [
					['name' => 'Qurilish va ta’mirlash', 'is_page' => true],
					['name' => 'Usta xizmatlari', 'is_page' => true],
					['name' => 'Avto xizmatlar', 'is_page' => true],
					['name' => 'IT va texnik xizmatlar', 'is_page' => true],
				]
			],

			// 🪑 Mebel
			[
				'name' => 'Mebel',
				'image' => '/icons/furniture.svg',
				'subs' => [
					['name' => 'Divan / Krovat', 'is_page' => true],
					['name' => 'Stol / Stul', 'is_page' => true],
					['name' => 'Shkaf', 'is_page' => true],
					['name' => 'Oshxona mebeli', 'is_page' => true],
				]
			],

			// 🎓 Ta’lim
			[
				'name' => "Talim",
				'image' => '/icons/education.svg',
				'subs' => [
					['name' => 'Kurslar', 'is_page' => true],
					['name' => 'Repetitor', 'is_page' => true],
					['name' => 'Online darslar', 'is_page' => true],
				]
			],

			// 🐄 Chorva va hayvonlar
			[
				'name' => 'Hayvonot',
				'image' => '/icons/animal.svg',
				'subs' => [
					['name' => 'Qoramol', 'is_page' => true],
					['name' => "Qo'y / Echki", 'is_page' => true],
					['name' => 'Parrandalar', 'is_page' => true],
					['name' => 'Uy hayvonlari', 'is_page' => true],
				]
			],

			// 👕 Shaxsiy buyumlar
			[
				'name' => 'Shaxsiy buyumlar',
				'image' => '/icons/personal.svg',
				'subs' => [
					['name' => 'Kiyim-kechak', 'is_page' => true],
					['name' => 'Poyabzal', 'is_page' => true],
					['name' => 'Parfyumeriya va kosmetika', 'is_page' => true],
					['name' => 'Aksessuarlar', 'is_page' => true],
				]
			],

			// 🔎 Topildi / Yo‘qotildi
			[
				'name' => "Topildi / Yo'qotildi",
				'image' => '/icons/lost.svg',
				'subs' => [
					['name' => 'Topildi', 'is_page' => true],
					['name' => "Yo'qotildi", 'is_page' => true],
				]
			],
			// 🎁 Bepul beriladi
			[
				'name' => 'Bepul beriladi',
				'image' => '/icons/free.svg',
				'is_page' => true
			],

			// 📦 Boshqa
			[
				'name' => 'Boshqa',
				'image' => '/icons/other.svg',
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
