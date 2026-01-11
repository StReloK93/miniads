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
		$categories = [
			[
				'name' => 'Ko\'chmas mulk',
				'image' => '/icons/house.svg',
				'subs' => ['Kvartiralar', 'Hovli va Uylar']
			],
			[
				'name' => 'Transport',
				'image' => '/icons/car.svg',
				'subs' => ['Yengil avtomobillar', 'Ehtiyot qismlar']
			],
			[
				'name' => 'Elektronika',
				'image' => '/icons/electronics.svg',
				'subs' => ['Telefonlar', 'Kompyuter texnikasi']
			],
			[
				'name' => 'Chorvachilik',
				'image' => '/icons/music.svg',
				'subs' => ['Qoramollar', 'Qo\'y va Echkilar']
			],
			[
				'name' => 'Xizmatlar',
				'image' => '/icons/services.svg',
				'subs' => ['Usta xizmatlari', 'Yuk tashish']
			],
		];

		foreach ($categories as $item) {
			// 1. Asosiy kategoriyani yaratamiz
			$parent = Category::create([
				'name' => $item['name'],
				'image' => $item['image'],
				'parent_id' => null,
			]);

			// 2. Unga tegishli podkategoriyalarni aylantiramiz
			foreach ($item['subs'] as $subName) {
				Category::create([
					'name' => $subName,
					'is_page' => true, // Podkategoriyalar "Sahifa" turida bo'ladi
					'image' => null, // Podkategoriyalarga rasm shart emas
					'parent_id' => $parent->id, // Otaning ID-sini bog'laymiz
				]);
			}
		}




	}
}
