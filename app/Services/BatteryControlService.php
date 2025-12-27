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
        $battery = BatterySetting::latest()->first();
        if (!$battery) {
            return;
        }

        // Solar sell / force discharge logic
        $currentSolarPrice = $prices['solarPrice'];
        $targetSolarPrice = $battery->target_price_cents;
        $shouldForceDischarge = $currentSolarPrice > $targetSolarPrice;

        if ($battery->forced_discharge !== $shouldForceDischarge) {
            $dischargeStartHour = (int) substr($battery->discharge_start_time, 0, 2);

            $currentHour = now()->hour;
            if ($currentHour >= 0 && $currentHour < 3) {
                $dischargeStartHour = 0;
            }

            app(FoxEssService::class)->setForcedChargeorDischarge($shouldForceDischarge, $dischargeStartHour, 'ForceDischarge');

            $battery->update(['forced_discharge' => $shouldForceDischarge]);
            BatteryTransaction::create([
                'datetime' => now(),
                'price_cents' => $currentSolarPrice,
                'action' => $shouldForceDischarge ? 'FORCE_DISCHARGE_ON' : 'FORCE_DISCHARGE_OFF',
                'battery_id' => $battery->id,
            ]);
        }

        // Grid buy / force charge logic
        $currentElectricPrice = $prices['electricityPrice'];
        $targetElectricPrice = $battery->target_electric_price_cents;
        $shouldForceCharge = $currentElectricPrice < $targetElectricPrice;

        if ($battery->forced_charge !== $shouldForceCharge) {
            $chargeStartHour = (int) substr($battery->charge_start_time, 0, 2);
            app(FoxEssService::class)->setForcedChargeorDischarge($shouldForceCharge, $chargeStartHour, 'ForceCharge');

            $battery->update(['forced_charge' => $shouldForceCharge]);

            BatteryTransaction::create([
                'datetime' => now(),
                'price_cents' => $currentElectricPrice,
                'action' => $shouldForceCharge ? 'FORCE_CHARGE_ON' : 'FORCE_CHARGE_OFF',
                'battery_id' => $battery->id,
            ]);
        }
    }
}
