<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parameter;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $parameters = [
            [
                'placeholder' => 'Xonalar soni',
                'type' => 'InputNumber',
                'unit' => null,
                'options' => null
            ],
            [
                'placeholder' => 'Maydoni',
                'type' => 'InputNumber',
                'unit' => 'm²',
                'options' => null
            ],
            [
                'placeholder' => 'Qavat',
                'type' => 'InputNumber',
                'unit' => null,
                'options' => null
            ],
            [
                'placeholder' => 'Tamir turi',
                'type' => 'Select',
                'unit' => null,
                'options' => ['Yomon', "O'rtacha", 'Yaxshi', "Zo'r"]
            ],
            [
                'placeholder' => 'Rangi',
                'type' => 'Select',
                'unit' => null,
                'options' => ['Oq', "Qora", 'Sariq', "Yashil"]
            ],
        ];

        foreach ($parameters as $parameter) {
            Parameter::create($parameter);
        }
    }
}
