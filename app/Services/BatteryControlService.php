<?php

namespace App\Services;

use App\BatterySetting;
use App\BatteryTransaction;
use App\Models\BatteryStrategy;
use App\Models\SellPlan;
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
        $forecastPrice = $battery->target_price_cents;

        $recentSellPlans = SellPlan::where('created_at', '>=', Carbon::now()->subMinutes(15))->get();
        $isSellWindow = false;
        foreach ($recentSellPlans as $plan) {
            if (Carbon::now()->format('H:i') >= Carbon::parse($plan->time)->format('H:i')) {
                $isSellWindow = true;
                break;
            }
        }
        if ($isSellWindow) {
            // Manage historical prices
            $solarPrices = Cache::get('solar_prices', []);
            array_unshift($solarPrices, $currentSolarPrice);
            $solarPrices = array_slice($solarPrices, 0, 6);
            Cache::put('solar_prices', $solarPrices, now()->addMinutes(60));

            // Manage forecast errors
            $forecastErrors = Cache::get('forecast_errors', []);
            if ($currentSolarPrice < $forecastPrice) {
                $error = $forecastPrice - $currentSolarPrice;
                array_unshift($forecastErrors, $error);
                $forecastErrors = array_slice($forecastErrors, 0, 6);
                Cache::put('forecast_errors', $forecastErrors, now()->addMinutes(60));
            }

            // Calculate metrics
            $avg_error = count($forecastErrors) > 0 ? array_sum($forecastErrors) / count($forecastErrors) : 0;
            $P_forecast_high = $this->clamp($avg_error / 5, 0, 1);

            $P_momentum = 0.0;
            if (count($solarPrices) >= 3) {
                if ($solarPrices[1] > $solarPrices[2] && $solarPrices[1] > $solarPrices[0]) {
                    $P_momentum = 1.0;
                } elseif ($solarPrices[0] < $solarPrices[1]) {
                    $P_momentum = 0.6;
                }
            }

            $volatility = $this->stddev($solarPrices);
            $P_spike = $this->clamp($volatility / 3, 0, 1);

            $sell_score =
                0.4 * $P_forecast_high +
                0.4 * $P_momentum +
                0.2 * $P_spike;

            $threshold = 0.65;
            $haircut = 0.90;
            $floor = $battery->longterm_target_price_cents;

            $offer_price = max($forecastPrice * $haircut, $floor);
            Cache::put('offer_price', $offer_price, now()->addMinutes(5));
            Cache::put('sell_score', $sell_score, now()->addMinutes(5));
            Cache::put('threshold', $threshold, now()->addMinutes(5));
            if($offer_price>$forecastPrice)
            $offer_price=$forecastPrice;
            $shouldForceDischarge = ($sell_score >= $threshold || $currentSolarPrice >= $offer_price) && $battery->status !== 'self_sufficient';
        } else {
            Cache::forget('offer_price');
            Cache::forget('sell_score');
            Cache::forget('threshold');
            Cache::forget('solar_prices');
            Cache::forget('forecast_errors');
            $shouldForceDischarge = $currentSolarPrice > $forecastPrice && $battery->status !== 'self_sufficient';
        }


        if ($battery->forced_discharge === $shouldForceDischarge) {
            return;
        }

        $dischargeStartHour = 0;
        $currentHour = now()->hour;
        $isOvernightWindow = false;
        if ($overnightStrategy) {
            $overnightStartHour = Carbon::parse($overnightStrategy->sell_start_time)->hour;
            $overnightEndHour = Carbon::parse($overnightStrategy->sell_end_time)->hour;
            $isOvernightWindow = ($currentHour >= $overnightStartHour && $currentHour <= $overnightEndHour);
        }
        
        // Set the discharge hour based on priority: overnight window first, then evening peak
        if ($isOvernightWindow) {
            $dischargeStartHour = $overnightStartHour;
        } elseif ($eveningPeakStrategy) {
            $dischargeStartHour = Carbon::parse($eveningPeakStrategy->sell_start_time)->hour;
        }

        app(FoxEssService::class)->setForcedChargeorDischarge($shouldForceDischarge, $dischargeStartHour, 'ForceDischarge');

        $battery->update(['forced_discharge' => $shouldForceDischarge]);
        if (!isset($currentSolarPrice)) {
            $currentSolarPrice = 0;
        }
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
        $shouldForceCharge = $currentElectricPrice < $targetElectricPrice && $battery->status !== 'self_sufficient';

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

    private function clamp($value, $min, $max)
    {
        return max($min, min($max, $value));
    }

    private function stddev($array)
    {
        $n = count($array);
        if ($n === 0) {
            return 0;
        }
        $mean = array_sum($array) / $n;
        $variance = 0.0;
        foreach ($array as $x) {
            $variance += pow($x - $mean, 2);
        }
        return (float) sqrt($variance / $n);
    }
}
