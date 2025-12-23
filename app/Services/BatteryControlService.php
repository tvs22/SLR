<?php

namespace App\Services;

use App\BatterySetting;
use App\BatteryTransaction;
use Illuminate\Support\Facades\Cache;

class BatteryControlService
{
    public function run()
    {
        $prices = app(AmberService::class)->getLatestPrices();
        Cache::put('latest_prices', $prices, now()->addMinutes(10));
        $battery = BatterySetting::first();
        $current=1;
        $current = $prices['solarPrice'];
        $target = $battery->target_price_cents;
        $desired = $current > $target;

        if ($battery->forced_discharge !== $desired) {
            app(FoxEssService::class)->setForcedDischarge($desired);

            $battery->update(['forced_discharge' => $desired]);

            BatteryTransaction::create([
                'datetime' => now(),
                'price_cents' => $current,
                'action' => $desired ? 'FORCE_DISCHARGE_ON' : 'FORCE_DISCHARGE_OFF',
                'battery_id' => $battery->id,
            ]);
        }
        $currentElectricPrice = 33;
        $currentElectricPrice = $prices['electricityPrice'];
        $targetElecrticPrice = $battery->target_electric_price_cents;
        $desiredForceCharge = $currentElectricPrice < $targetElectricPrice;
        if ($battery->forced_charge !== $desiredForceCharge) {

        }
    }
}
