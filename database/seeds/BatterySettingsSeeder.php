<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            'forced_discharge' => FALSE,
            'discharge_start_time' => '16:00:00',
            'target_electric_price_cents' => 6.00,
            'forced_charge' => FALSE,
            'charge_start_time' => '11:00:00',
            'battery_level_percent' => 50.00,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
