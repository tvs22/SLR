<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PvForecastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pv_yields')->insert([
            ['date' => '2026-01-02', 'hour' => 7, 'kwh' => 0.10],
            ['date' => '2026-01-02', 'hour' => 8, 'kwh' => 0.60],
            ['date' => '2026-01-02', 'hour' => 9, 'kwh' => 1.70],
            ['date' => '2026-01-02', 'hour' => 10, 'kwh' => 3.30],
            ['date' => '2026-01-02', 'hour' => 11, 'kwh' => 5.40],
            ['date' => '2026-01-02', 'hour' => 12, 'kwh' => 7.00],
            ['date' => '2026-01-02', 'hour' => 13, 'kwh' => 8.20],
            ['date' => '2026-01-02', 'hour' => 14, 'kwh' => 9.40],
            ['date' => '2026-01-02', 'hour' => 15, 'kwh' => 10.20],
            ['date' => '2026-01-02', 'hour' => 16, 'kwh' => 10.80],
            ['date' => '2026-01-02', 'hour' => 17, 'kwh' => 11.20],
            ['date' => '2026-01-02', 'hour' => 18, 'kwh' => 11.80],
            ['date' => '2026-01-02', 'hour' => 19, 'kwh' => 12.00],
            ['date' => '2026-01-02', 'hour' => 20, 'kwh' => 12.00],
        ]);
    }
}
