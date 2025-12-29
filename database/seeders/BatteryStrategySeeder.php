<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BatteryStrategy;

class BatteryStrategySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BatteryStrategy::create([
            'name' => 'Evening Peak',
            'description' => 'Sell energy above 75% SOC during peak hours.',
            'sell_start_time' => '19:00:00',
            'sell_end_time' => '21:00:00',
            'buy_start_time' => '11:00:00',
            'buy_end_time' => '13:00:00',
            'soc_lower_bound' => 75,
            'soc_upper_bound' => 100,
            'strategy_group' => null,
            'is_active' => true,
        ]);

        BatteryStrategy::create([
            'name' => 'Flexible Evening',
            'description' => 'Sell energy between 40% and 75% SOC during the evening.',
            'sell_start_time' => '19:00:00',
            'sell_end_time' => '21:00:00',
            'buy_start_time' => null,
            'buy_end_time' => null,
            'soc_lower_bound' => 40,
            'soc_upper_bound' => 75,
            'strategy_group' => 'flexible_40_75',
            'is_active' => true,
        ]);

        BatteryStrategy::create([
            'name' => 'Overnight',
            'description' => 'Sell energy between 30% and 40% SOC overnight.',
            'sell_start_time' => '00:00:00',
            'sell_end_time' => '02:30:00',
            'buy_start_time' => null,
            'buy_end_time' => null,
            'soc_lower_bound' => 30,
            'soc_upper_bound' => 40,
            'strategy_group' => null,
            'is_active' => true,
        ]);
    }
}
