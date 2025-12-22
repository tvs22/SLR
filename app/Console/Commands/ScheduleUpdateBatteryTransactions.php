<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\BatteryTransaction;
use App\BatterySetting;
use Carbon\Carbon;

class ScheduleUpdateBatteryTransactions extends Command
{
    protected $signature = 'app:schedule-update-battery-transactions';

    protected $description = 'Checks the current price and creates a battery transaction accordingly.';

    public function handle()
    {
        // Placeholder for fetching the current price
        $currentPrice = 25.50;

        $batterySetting = BatterySetting::latest()->first();

        if (!$batterySetting) {
            $this->error('No battery settings found.');
            return;
        }

        $action = 'do_nothing'; // Placeholder

        if ($currentPrice > $batterySetting->target_price_cents) {
            $action = 'discharge';
        } else {
            $action = 'charge';
        }

        BatteryTransaction::create([
            'datetime' => Carbon::now(),
            'price_cents' => $currentPrice,
            'action' => $action,
            'battery_id' => $batterySetting->id,
        ]);

        $this->info('Battery transaction created successfully.');
    }
}
