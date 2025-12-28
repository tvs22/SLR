<?php

namespace App\Http\Controllers;

use App\Services\AmberService;
use App\Services\FoxEssService;
use App\SolarForecast;
use App\BatterySetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PriceController extends Controller
{
    public function getPredictedPrices(AmberService $amberService, FoxEssService $foxEssService): JsonResponse
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentHour = $now->hour;
        $minute = $now->minute;

        $forecastsToday = SolarForecast::where('date', $today)->orderBy('hour', 'asc')->get();
        $batterySettings = BatterySetting::latest()->first();
        $socToKwhFactor=0.4193;

        if ($forecastsToday->isEmpty()) {
            $soc = $foxEssService->getSoc();
            $kwhToBuy = $soc !== null ? round((100 - $soc) * $socToKwhFactor, 2) : 0;

            Cache::put('soc', $soc, now()->addMinutes(30));
            Cache::put('remaining_solar_generation_today', 0, now()->addMinutes(30));
            Cache::put('forecast_soc', $soc, now()->addMinutes(30));
            Cache::put('kwh_to_buy', $kwhToBuy, now()->addMinutes(30));

            return response()->json([
                'soc' => $soc,
                'remaining_solar_generation_today' => 0,
                'forecast_soc' => $soc,
                'kwh_to_buy' => $kwhToBuy,
                'buy_plan' => [],
            ]);
        }

        $totalDayGeneration = $forecastsToday->last()->kwh;
        $generatedSoFarToday = 0;

        if ($currentHour < $forecastsToday->first()->hour) {
            $generatedSoFarToday = 0;
        } elseif ($currentHour >= $forecastsToday->last()->hour) {
            $generatedSoFarToday = $totalDayGeneration;
        } else {
            $currentHourForecast = $forecastsToday->where('hour', $currentHour)->first();

            if (!$currentHourForecast) {
                $prevForecast = $forecastsToday->where('hour', '<', $currentHour)->last();
                $generatedSoFarToday = $prevForecast ? $prevForecast->kwh : 0;
            } else {
                $nextHourForecast = $forecastsToday->where('hour', $currentHour + 1)->first();
                $kwhAtStartOfHour = $currentHourForecast->kwh;

                if ($nextHourForecast) {
                    $kwhAtEndOfHour = $nextHourForecast->kwh;
                    $generationDuringHour = $kwhAtEndOfHour - $kwhAtStartOfHour;
                    $generatedSoFarToday = $kwhAtStartOfHour + ($generationDuringHour * ($minute / 60));
                } else {
                    $generatedSoFarToday = $currentHourForecast->kwh;
                }
            }
        }

        $remainingGeneration = $totalDayGeneration - $generatedSoFarToday;
        $remainingGeneration = max(0, $remainingGeneration);

        $soc = $foxEssService->getSoc();
        $forecastSoc = null;
        $kwhToBuy = null;
        $eveningSellStrategy = null;
        $lateNightSellStrategy = null;

        if ($soc !== null) {
            $forecastSoc = $soc + ($remainingGeneration / $socToKwhFactor);
            $forecastSoc = min(100, round($forecastSoc));
            $gapSoc = 100 - $forecastSoc;
            $kwhToBuy = round($gapSoc * $socToKwhFactor, 2);

            // Evening Sell Strategy (19:00 - 23:59)
            $kwhToSellEvening = ($soc > 40) ? (min($soc, 100) - 40) * $socToKwhFactor : 0;
            if ($kwhToSellEvening > 0) {
                $eveningSellStrategy = $amberService->calculateOptimalDischarging($kwhToSellEvening, $batterySettings->longterm_target_price_cents, now()->setTime(19, 0), now()->setTime(23, 59));
                Cache::put('evening_sell_strategy', $eveningSellStrategy, now()->addMinutes(30));
            }

            // Late Night Sell Strategy (00:00 - 02:30)
            $kwhToSellLateNight = ($soc > 30) ? (min($soc, 40) - 30) * $socToKwhFactor : 0;
            if ($kwhToSellLateNight > 0) {
                $lateNightSellStrategy = $amberService->calculateOptimalDischarging($kwhToSellLateNight, $batterySettings->longterm_target_price_cents, now()->addDay()->setTime(0, 0), now()->addDay()->setTime(2, 30));
                Cache::put('late_night_sell_strategy', $lateNightSellStrategy, now()->addMinutes(30));
            }
        }

        Cache::put('soc', $soc, now()->addMinutes(30));
        Cache::put('remaining_solar_generation_today', round($remainingGeneration, 2), now()->addMinutes(30));
        Cache::put('forecast_soc', $forecastSoc, now()->addMinutes(30));
        Cache::put('kwh_to_buy', $kwhToBuy, now()->addMinutes(30));

        if ($kwhToBuy > 0) {
            $buyStrategy = $amberService->calculateOptimalCharging(
                $kwhToBuy,
                $batterySettings->longterm_target_electric_price_cents,
                $batterySettings->longterm_target_price_cents
            );
            Cache::put('buy_strategy', $buyStrategy, now()->addMinutes(30));
        } else {
            $buyStrategy = ['buy_plan' => []];
            Cache::put('buy_strategy', ['message' => 'Battery does not require charging.'], now()->addMinutes(30));
        }

        return response()->json([
            'soc' => $soc,
            'remaining_solar_generation_today' => round($remainingGeneration, 2),
            'forecast_soc' => $forecastSoc,
            'kwh_to_buy' => $kwhToBuy,
            'buy_plan' => $buyStrategy['buy_plan'] ?? [],
            'evening_sell_strategy' => $eveningSellStrategy['sell_plan'] ?? [],
            'late_night_sell_strategy' => $lateNightSellStrategy['sell_plan'] ?? [],
        ]);
    }
}
