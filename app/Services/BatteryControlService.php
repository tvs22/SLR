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

        $battery = Cache::remember('battery_settings', 60, function () {
            return BatterySetting::latest()->first();
        });

        if (!$battery) {
            return;
        }

        $batteryStrategies = Cache::remember('battery_strategies', 60, function () {
            return BatteryStrategy::whereIn('name', ['Evening Peak', 'Overnight'])->get()->keyBy('name');
        });

        $eveningPeakStrategy = $batteryStrategies->get('Evening Peak');
        $overnightStrategy = $batteryStrategies->get('Overnight');

        $this->updateLongtermTargetPrice($battery, $overnightStrategy);
        $this->handleForcedDischarge($battery, $prices, $eveningPeakStrategy, $overnightStrategy);
        $this->handleForcedCharge($battery, $prices, $eveningPeakStrategy);
    }

    private function updateLongtermTargetPrice($battery, $overnightStrategy)
    {
        if (!$overnightStrategy) {
            return;
        }

        $currentHour = now()->hour;
        $overnightStartHour = Carbon::parse($overnightStrategy->sell_start_time)->hour;
        $overnightEndHour = Carbon::parse($overnightStrategy->sell_end_time)->hour;

        $isOvernight = $currentHour >= $overnightStartHour && $currentHour < $overnightEndHour;

        $targetPrice = $isOvernight
            ? env('OVERNIGHT_LONGTERM_TARGET_PRICE', 7)
            : env('DEFAULT_LONGTERM_TARGET_PRICE', 4);

        if ($isOvernight && $battery->longterm_target_price_cents < $targetPrice) {
            $battery->update(['longterm_target_price_cents' => $targetPrice]);
        } elseif (!$isOvernight) {
            $battery->update(['longterm_target_price_cents' => $targetPrice]);
        }
    }

    private function handleForcedDischarge($battery, $prices, $eveningPeakStrategy, $overnightStrategy)
    {
        $currentSolarPrice = $prices['solarPrice'];
        $targetSolarPrice = $battery->target_price_cents;
        $shouldForceDischarge = $currentSolarPrice > $targetSolarPrice && $battery->status !== 'self_sufficient';

        if ($battery->forced_discharge === $shouldForceDischarge) {
            return;
        }

        $dischargeStartHour = 0;
        $currentHour = now()->hour;

        if ($overnightStrategy) {
            $overnightStartHour = Carbon::parse($overnightStrategy->sell_start_time)->hour;
            $overnightEndHour = Carbon::parse($overnightStrategy->sell_end_time)->hour;
            if ($currentHour >= $overnightStartHour && $currentHour <= $overnightEndHour) {
                $dischargeStartHour = $overnightStartHour;
            }
        } elseif ($eveningPeakStrategy) {
            $dischargeStartHour = Carbon::parse($eveningPeakStrategy->sell_start_time)->hour;
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

    private function handleForcedCharge($battery, $prices, $chargeStrategy)
    {
        $currentElectricPrice = $prices['electricityPrice'];
        $targetElectricPrice = $battery->target_electric_price_cents;
        $shouldForceCharge = $currentElectricPrice < $targetElectricPrice;

        if ($battery->forced_charge === $shouldForceCharge) {
            return;
        }

        $chargeStartHour = 0;
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
