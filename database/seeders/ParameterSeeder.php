<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parameter;

class ParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
