<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategoryParameter;

class CategoryParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $CategoryParameters = [
            [
                'category_id' => 2,
                'is_required' => false,
                'parameter_id' => 1,
                'sort_order' => 1,
            ],
            [
                'category_id' => 2,
                'is_required' => true,
                'parameter_id' => 2,
                'sort_order' => 2,
            ],
            [
                'category_id' => 2,
                'is_required' => true,
                'parameter_id' => 3,
                'sort_order' => 3,
            ],
            [
                'category_id' => 2,
                'is_required' => false,
                'parameter_id' => 4,
                'sort_order' => 4,
            ],
        ];

        CategoryParameter::insert($CategoryParameters);

        // foreach ($CategoryParameters as $parameter) {
        //     CategoryParameter::create($parameter);
        // }
    }
}
