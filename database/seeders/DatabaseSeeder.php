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
                'component' => 'InputNumber',
                'type' => 'number',
                'unit' => null,
                'options' => null
            ],
            [
                'placeholder' => 'Maydoni',
                'component' => 'InputNumber',
                'type' => 'number',
                'unit' => 'm²',
                'options' => null
            ],
            [
                'placeholder' => 'Qavat',
                'component' => 'InputNumber',
                'type' => 'number',
                'unit' => null,
                'options' => null
            ],
            [
                'placeholder' => 'Tamir turi',
                'component' => 'Select',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yomon', "O'rtacha", 'Yaxshi', "Zo'r"]
            ],
            [
                'placeholder' => 'Rangi',
                'component' => 'Select',
                'type' => 'string',
                'unit' => null,
                'options' => ['Oq', "Qora", 'Sariq', "Yashil"]
            ],
        ];

        foreach ($parameters as $parameter) {
            Parameter::create($parameter);
        }
    }
}
