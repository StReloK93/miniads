<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::create([
            'name' => 'Bumin Xoqon',
            'telegram_user_id' => 100,
            'role' => 'admin'
        ]);
        $this->call(CategorySeeder::class);
        $this->call(ParameterSeeder::class);
        $this->call(CategoryParameterSeeder::class);

        $this->call(PriceTypeSeeder::class);

    }
}
