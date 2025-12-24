<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatterySocSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $SOC_PLAN = [
            10 => 20, 11 => 30, 12 => 40, 13 => 50,
            14 => 60, 15 => 70, 16 => 80, 17 => 90,
            18 => 100, 19 => 100, 20 => 70, 21 => 40
        ];

        foreach ($SOC_PLAN as $hour => $soc) {
            DB::table('battery_soc')->insert([
                'hour' => $hour,
                'soc' => $soc,
                'type' => 'soc_plans',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        $SOC_Low_PLAN = [
            10 => 10, 11 => 15, 12 => 20, 13 => 25,
            14 => 30, 15 => 35, 16 => 40, 17 => 45,
            18 => 50
        ];

        foreach ($SOC_Low_PLAN as $hour => $soc) {
            DB::table('battery_soc')->insert([
                'hour' => $hour,
                'soc' => $soc,
                'type' => 'soc_low_plans',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
