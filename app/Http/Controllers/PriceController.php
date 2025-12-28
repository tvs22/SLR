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
        $socToKwhFactor = 0.4193;

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
        $buyStrategy = ['buy_plan' => []];
        $eveningSellStrategy = null;
        $lateEveningSellStrategy = null;
        $lateNightSellStrategy = null;

        if ($soc !== null) {
            $forecastSoc = $soc + ($remainingGeneration / $socToKwhFactor);
            $forecastSoc = min(100, round($forecastSoc));
            $gapSoc = 100 - $forecastSoc;
            $kwhToBuy = round($gapSoc * $socToKwhFactor, 2);

            $kwhAbove75 = ($soc > 75) ? ($soc - 75) * $socToKwhFactor : 0;
            $kwh75to40 = ($soc > 40) ? (min($soc, 75) - 40) * $socToKwhFactor : 0;
            $kwhToSellLateNight = ($soc > 30) ? (min($soc, 40) - 30) * $socToKwhFactor : 0;

            // 1. Calculate all potential plans first.
            $guaranteedEveningPlan = null;
            if ($kwhAbove75 > 0) {
                $guaranteedEveningPlan = $amberService->calculateOptimalDischarging($kwhAbove75, $batterySettings->longterm_target_price_cents, now()->setTime(19, 0), now()->setTime(21, 0));
            }

            if ($kwh75to40 > 0) {
                $flexibleEveningPlan = $amberService->calculateOptimalDischarging($kwh75to40, $batterySettings->longterm_target_price_cents, now()->setTime(19, 0), now()->setTime(21, 0));
                $flexibleLateEveningPlan = $amberService->calculateOptimalDischarging($kwh75to40, $batterySettings->longterm_target_price_cents, now()->setTime(21, 0), now()->setTime(23, 59));
                
                // 2. Compare and assign, don't recalculate.
                if (($flexibleLateEveningPlan['total_revenue'] ?? 0) > ($flexibleEveningPlan['total_revenue'] ?? 0)) {
                    // Late evening is more profitable for the flexible block.
                    $eveningSellStrategy = $guaranteedEveningPlan;
                    $lateEveningSellStrategy = $flexibleLateEveningPlan; // Assign the pre-calculated plan.
                } else {
                    // Early evening is more (or equally) profitable. Merge the guaranteed and flexible plans.
                    $eveningSellStrategy = $this->mergeSellPlans($guaranteedEveningPlan, $flexibleEveningPlan);
                    $lateEveningSellStrategy = null; // Ensure it's null.
                }
            } else {
                // No flexible block to sell, so the evening strategy is just the guaranteed plan.
                $eveningSellStrategy = $guaranteedEveningPlan;
            }
            if ($kwhToSellLateNight > 0) {
                $lateNightSellStrategy = $amberService->calculateOptimalDischarging($kwhToSellLateNight, $batterySettings->longterm_target_price_cents, now()->addDay()->setTime(0, 0), now()->addDay()->setTime(2, 30));
            }
            
            if ($kwhToBuy > 0) {
                $buyStrategy = $amberService->calculateOptimalCharging(
                    $kwhToBuy,
                    $batterySettings->longterm_target_electric_price_cents,
                    $batterySettings->longterm_target_price_cents
                );
            }
        }

        // Cache all the results for polling
        Cache::put('soc', $soc, now()->addMinutes(30));
        Cache::put('remaining_solar_generation_today', round($remainingGeneration, 2), now()->addMinutes(30));
        Cache::put('forecast_soc', $forecastSoc, now()->addMinutes(30));
        Cache::put('kwh_to_buy', $kwhToBuy, now()->addMinutes(30));
        Cache::put('buy_strategy', $buyStrategy, now()->addMinutes(30));
        Cache::put('evening_sell_strategy', $eveningSellStrategy, now()->addMinutes(30));
        Cache::put('late_evening_sell_strategy', $lateEveningSellStrategy, now()->addMinutes(30));
        Cache::put('late_night_sell_strategy', $lateNightSellStrategy, now()->addMinutes(30));

        // Return the fresh data directly
        return response()->json([
            'soc' => $soc,
            'remaining_solar_generation_today' => round($remainingGeneration, 2),
            'forecast_soc' => $forecastSoc,
            'kwh_to_buy' => $kwhToBuy,
            'buyStrategy' => $buyStrategy,
            'evening_sell_strategy' => $eveningSellStrategy,
            'late_evening_sell_strategy' => $lateEveningSellStrategy,
            'late_night_sell_strategy' => $lateNightSellStrategy,
        ]);
    }
    
    /**
     * Merges two or more sell plans into a single plan.
     *
     * @param array|null ...$plans
     * @return array|null
     */
    private function mergeSellPlans(...$plans): ?array
    {
        $nonNullPlans = array_filter($plans, function($plan) {
            return !empty($plan) && empty($plan['error']) && !empty($plan['sell_plan']);
        });

        if (empty($nonNullPlans)) {
            // Return one of the original plans if it contains a message (e.g., "no profitable slots")
            foreach ($plans as $plan) {
                if (!empty($plan['message'])) {
                    return $plan;
                }
            }
            return null;
        }
        
        if (count($nonNullPlans) === 1) {
            return array_pop($nonNullPlans);
        }

        $mergedPlan = [
            'total_kwh_sold' => 0,
            'total_revenue' => 0,
            'highest_sell_price' => 0,
            'lowest_sell_price' => PHP_INT_MAX,
            'highest_sell_price_time' => null,
            'sell_plan' => [],
            'error' => null,
            'message' => null,
        ];

        foreach ($nonNullPlans as $plan) {
            $mergedPlan['total_kwh_sold'] += $plan['total_kwh_sold'];
            $mergedPlan['total_revenue'] += $plan['total_revenue'];

            if ($plan['highest_sell_price'] > $mergedPlan['highest_sell_price']) {
                $mergedPlan['highest_sell_price'] = $plan['highest_sell_price'];
                $mergedPlan['highest_sell_price_time'] = $plan['highest_sell_price_time'];
            }

            if ($plan['lowest_sell_price'] < $mergedPlan['lowest_sell_price']) {
                $mergedPlan['lowest_sell_price'] = $plan['lowest_sell_price'];
            }
            
            $mergedPlan['sell_plan'] = array_merge($mergedPlan['sell_plan'], $plan['sell_plan']);
        }

        if ($mergedPlan['lowest_sell_price'] === PHP_INT_MAX) {
            $mergedPlan['lowest_sell_price'] = 0;
        }
        
        // Sort the final combined plan by time
        usort($mergedPlan['sell_plan'], function ($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return $mergedPlan;
    }
}
