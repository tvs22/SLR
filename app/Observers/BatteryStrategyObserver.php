<?php

namespace App\Observers;

use App\Models\BatteryStrategy;
use Illuminate\Support\Facades\Cache;

class BatteryStrategyObserver
{
    /**
     * Handle the BatteryStrategy "updated" event.
     *
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return void
     */
    public function updated(BatteryStrategy $batteryStrategy)
    {
        Cache::forget('battery_strategies');
    }

    /**
     * Handle the BatteryStrategy "created" event.
     *
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return void
     */
    public function created(BatteryStrategy $batteryStrategy)
    {
        Cache::forget('battery_strategies');
    }

    /**
     * Handle the BatteryStrategy "deleted" event.
     *
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return void
     */
    public function deleted(BatteryStrategy $batteryStrategy)
    {
        Cache::forget('battery_strategies');
    }
}
