<?php

namespace App\Observers;

use App\BatterySetting;
use Illuminate\Support\Facades\Cache;

class BatterySettingObserver
{
    /**
     * Handle the BatterySetting "updated" event.
     *
     * @param  \App\BatterySetting  $batterySetting
     * @return void
     */
    public function updated(BatterySetting $batterySetting)
    {
        Cache::forget('battery_settings');
        Cache::forget('battery_strategies'); // Also forget strategies as they might be related
    }

    /**
     * Handle the BatterySetting "created" event.
     *
     * @param  \App\BatterySetting  $batterySetting
     * @return void
     */
    public function created(BatterySetting $batterySetting)
    {
        Cache::forget('battery_settings');
        Cache::forget('battery_strategies');
    }

    /**
     * Handle the BatterySetting "deleted" event.
     *
     * @param  \App\BatterySetting  $batterySetting
     * @return void
     */
    public function deleted(BatterySetting $batterySetting)
    {
        Cache::forget('battery_settings');
        Cache::forget('battery_strategies');
    }
}
