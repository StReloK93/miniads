<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BackColor;
class BackColorSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            [
                'gradient' => "linear-gradient(135deg, oklch(27.4% 0.006 286.033), oklch(21% 0.006 285.885))",
                'border' => "border-zinc-800",
            ],
            [
                'gradient' => "linear-gradient(135deg, oklch(62% 0.18 20), oklch(55% 0.16 25))",
                'border' => "border-rose-300",
            ],
            [
                'gradient' => "linear-gradient(135deg, oklch(60% 0.16 250), oklch(65% 0.12 220))",
                'border' => "border-blue-400",
            ],
            [
                'gradient' => "linear-gradient(135deg, oklch(58% 0.20 300), oklch(60% 0.18 320))",
                'border' => "border-purple-400",
            ],
            [
                'gradient' => "linear-gradient(135deg, oklch(60% 0.14 160), oklch(55% 0.10 180))",
                'border' => "border-emerald-400",
            ],
        ];

        BackColor::insert($colors);

    }
}

