<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {



        $products = [
            [
                'user_id' => 1,
                'category_id' => 3,
                'price_type_id' => 1,
                'back_color_id' => 1,
                'price' => 1000000,
                'title' => 'Test Product',
                'description' => 'This is a test product.',
                'expires_at' => now()->addDays(10),
            ],
            [
                'user_id' => 1,
                'category_id' => 1,
                'price_type_id' => 1,
                'back_color_id' => 2,
                'price' => 150000,
                'title' => 'Test Product',
                'description' => 'This is a test product.',
                'expires_at' => now()->addDays(10),
            ],
            [
                'user_id' => 1,
                'category_id' => 3,
                'price_type_id' => 1,
                'back_color_id' => 3,
                'price' => 1000000,
                'title' => 'Test Product',
                'description' => 'This is a test product.',
                'expires_at' => now()->addDays(10),
            ],
            [
                'user_id' => 1,
                'category_id' => 3,
                'price_type_id' => 1,
                'back_color_id' => 4,
                'price' => 2000000,
                'title' => 'Test Product',
                'description' => 'This is a test product.',
                'expires_at' => now()->addDays(10),
            ],
            [
                'user_id' => 1,
                'category_id' => 1,
                'price_type_id' => 1,
                'back_color_id' => 5,
                'price' => 150000,
                'title' => 'Test Product',
                'description' => 'This is a test product.',
                'expires_at' => now()->addDays(10),
            ],
            [
                'user_id' => 1,
                'category_id' => 3,
                'price_type_id' => 1,
                'back_color_id' => 6,
                'price' => 1000000,
                'title' => 'Test Product',
                'description' => 'This is a test product.',
                'expires_at' => now()->addDays(10),
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}