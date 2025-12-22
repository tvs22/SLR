<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatteryTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('battery_transactions')->insert([
            [
                'datetime' => '2025-12-23 14:00:00',
                'price_cents' => 12.50,
                'action' => 'discharge',
                'battery_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'datetime' => '2025-12-23 22:00:00',
                'price_cents' => 11.80,
                'action' => 'charge',
                'battery_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
