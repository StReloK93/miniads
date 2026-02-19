<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PriceType;
class PriceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PriceType::create([
            'name' => 'UZS',
            'type' => "So'm",
            'position' => 'right'
        ]);

        PriceType::create([
            'name' => 'USD',
            'type' => "$",
            'position' => 'left'
        ]);
    }
}
