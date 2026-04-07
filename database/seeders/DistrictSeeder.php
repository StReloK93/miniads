<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $districts = [
            'Uchquduq',
            'Zarafshon',
            'Navoiy',
            'Karmana',
            'Qiziltepa',
        ];

        foreach ($districts as $district) {
            \App\Models\District::create(['name' => $district]);
        }
    }
}
