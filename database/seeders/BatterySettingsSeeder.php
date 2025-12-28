<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BatterySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('battery_settings')->insert([
            'target_price_cents' => 14.00,
           'longterm_target_price_cents'=>14.00,
            'forced_discharge' => false,
            'target_electric_price_cents' => 5.00,
            'longterm_target_electric_price_cents' => 5.00,
            'forced_charge' => false,
            'battery_level_percent' => 80.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
