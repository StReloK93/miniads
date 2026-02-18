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
                'title' => 'Xonalar soni',
                'placeholder' => 'Masalan: 3, 4',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'xona',
                'options' => null
            ],
            [
                'title' => 'Maydoni',
                'placeholder' => 'Masalan: 55m²',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'm²',
                'options' => null
            ],
            [
                'title' => 'Qavat',
                'placeholder' => 'Masalan: 2',
                'component' => 'FieldNumber',
                'type' => 'number',
                'unit' => 'qavat',
                'options' => null
            ],
            [
                'title' => 'Tamir turi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
                'type' => 'string',
                'unit' => null,
                'options' => ['Yomon', "O'rtacha", 'Yaxshi', "Zo'r"]
            ],
            [
                'title' => 'Rangi',
                'placeholder' => 'Tanlang',
                'component' => 'FieldSelect',
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
