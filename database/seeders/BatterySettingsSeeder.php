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
            'discharge_start_time' => '16:00:00',
            'target_electric_price_cents' => 5.00,
            'longterm_target_electric_price_cents' => 5.00,
            'forced_charge' => false,
            'charge_start_time' => '10:00:00',
            'battery_level_percent' => 80.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
