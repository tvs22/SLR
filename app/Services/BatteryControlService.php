<?php

namespace App\Services;

use App\BatterySetting;
use App\BatteryTransaction;
use App\Models\BatteryStrategy;
use Carbon\Carbon;
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

        $batteryStrategies = BatteryStrategy::whereIn('name', ['Evening Peak', 'Overnight'])->get()->keyBy('name');
        $eveningPeakStrategy = $batteryStrategies->get('Evening Peak');
        $overnightStrategy = $batteryStrategies->get('Overnight');

        // Solar sell / force discharge logic
        $currentSolarPrice = $prices['solarPrice'];
        $targetSolarPrice = $battery->target_price_cents;
        $shouldForceDischarge = $currentSolarPrice > $targetSolarPrice;
        if($battery->status=='self_sufficient')
            $shouldForceDischarge=false;

        if ($battery->forced_discharge !== $shouldForceDischarge) {
 
            if ($eveningPeakStrategy) {
                $dischargeStartHour = Carbon::parse($eveningPeakStrategy->sell_start_time)->hour;
            }

            $currentHour = now()->hour;
            if ($overnightStrategy) {
                $overnightStartHour = Carbon::parse($overnightStrategy->sell_start_time)->hour;
                $overnightEndHour = Carbon::parse($overnightStrategy->sell_end_time)->hour;

                if ($currentHour >= $overnightStartHour && $currentHour <= $overnightEndHour) {
                    $dischargeStartHour = $overnightStartHour;
                }
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
            $chargeStrategy = $batteryStrategies->get('Evening Peak');
            $chargeStartHour = 0; // Default to 00:00 if not found
            if ($chargeStrategy) {
                $chargeStartHour = Carbon::parse($chargeStrategy->buy_start_time)->hour;
            }
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
