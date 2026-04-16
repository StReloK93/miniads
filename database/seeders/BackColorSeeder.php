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
            // 1. Blue calm
            [
                'gradient' => "radial-gradient(circle at top left, rgba(96,165,250,.28), transparent 38%), radial-gradient(circle at bottom right, rgba(59,130,246,.22), transparent 42%), linear-gradient(135deg, #1e293b, #0f172a)",
                'border' => "rgba(96,165,250,1)",
            ],

            // 2. Emerald calm
            [
                'gradient' => "radial-gradient(circle at top left, rgba(52,211,153,.26), transparent 38%), radial-gradient(circle at bottom right, rgba(16,185,129,.20), transparent 42%), linear-gradient(135deg, #1f2937, #111827)",
                'border' => "rgba(52,211,153,1)",
            ],

            // 3. Purple soft
            [
                'gradient' => "radial-gradient(circle at top left, rgba(167,139,250,.26), transparent 38%), radial-gradient(circle at bottom right, rgba(139,92,246,.20), transparent 42%), linear-gradient(135deg, #1e1b4b, #020617)",
                'border' => "rgba(167,139,250,1)",
            ],

            // 4. Rose muted (issiq lekin yumshoq)
            [
                'gradient' => "radial-gradient(circle at top left, rgba(251,113,133,.22), transparent 38%), radial-gradient(circle at bottom right, rgba(244,63,94,.18), transparent 42%), linear-gradient(135deg, #2a1a1a, #111827)",
                'border' => "rgba(251,113,133,1)",
            ],

            // 5. Cyan calm
            [
                'gradient' => "radial-gradient(circle at top left, rgba(103,232,249,.24), transparent 38%), radial-gradient(circle at bottom right, rgba(6,182,212,.18), transparent 42%), linear-gradient(135deg, #0f2a30, #111827)",
                'border' => "rgba(103,232,249,1)",
            ],

            // 6. Neutral premium (eng vazmin)
            [
                'gradient' => "radial-gradient(circle at top left, rgba(255,255,255,.08), transparent 30%), radial-gradient(circle at bottom right, rgba(148,163,184,.10), transparent 36%), linear-gradient(135deg, #374151, #111827)",
                'border' => "rgba(10,10,10,1)",
            ],
        ];
        BackColor::insert($colors);

    }
}

