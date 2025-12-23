<?php

use Database\Seeders\BatterySettingsSeeder;
use Database\Seeders\BatteryTransactionsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(BatterySettingsSeeder::class);
    }
}
