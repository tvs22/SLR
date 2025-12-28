<?php

namespace App\Http\Controllers;

use App\Models\BatteryStrategy;
use App\Services\AmberService;
use App\Services\FoxEssService;
use App\SolarForecast;
use App\BatterySetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PriceController extends Controller
{
    private const SOC_TO_KWH_FACTOR = 0.4193;
    private const CACHE_MINUTES = 30;

    public function getPredictedPrices(AmberService $amberService, FoxEssService $foxEssService): JsonResponse
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentHour = $now->hour;
        $minute = $now->minute;

        $forecastsToday = SolarForecast::where('date', $today)->orderBy('hour', 'asc')->get();
        $batterySettings = BatterySetting::latest()->first();

        if ($forecastsToday->isEmpty()) {
            $soc = $foxEssService->getSoc();
            $kwhToBuy = $soc !== null ? round((100 - $soc) * self::SOC_TO_KWH_FACTOR, 2) : 0;

            Cache::put('soc', $soc, now()->addMinutes(self::CACHE_MINUTES));
            Cache::put('remaining_solar_generation_today', 0, now()->addMinutes(self::CACHE_MINUTES));
            Cache::put('forecast_soc', $soc, now()->addMinutes(self::CACHE_MINUTES));
            Cache::put('kwh_to_buy', $kwhToBuy, now()->addMinutes(self::CACHE_MINUTES));

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
        $batteryStrategies = $this->getActiveBatteryStrategies();
        $sellPlans = [];

        if ($soc !== null) {
            $forecastSoc = $soc + ($remainingGeneration / self::SOC_TO_KWH_FACTOR);
            $forecastSoc = min(100, round($forecastSoc));
            $gapSoc = 100 - $forecastSoc;
            $kwhToBuy = round($gapSoc * self::SOC_TO_KWH_FACTOR, 2);

            foreach ($batteryStrategies->groupBy('strategy_group') as $group => $strategies) {
                $bestPlan = null;
                $bestPlanRevenue = -1;

                foreach ($strategies as $strategy) {
                    $kwhToSell = $this->calculateKwhToSell($soc, $strategy);

                    if ($kwhToSell > 0) {
                        $startTime = Carbon::parse($strategy->sell_start_time);
                        $endTime = Carbon::parse($strategy->sell_end_time);

                        $effectiveStart = $now->max($startTime);

                        if ($effectiveStart < $endTime) {
                            $plan = $amberService->calculateOptimalDischarging(
                                $kwhToSell,
                                $batterySettings->longterm_target_price_cents,
                                $effectiveStart,
                                $endTime
                            );

                            if ($group && isset($plan['total_revenue'])) {
                                if ($plan['total_revenue'] > $bestPlanRevenue) {
                                    $bestPlan = $plan;
                                    $bestPlanRevenue = $plan['total_revenue'];
                                }
                            } else {
                                $sellPlans[] = $plan;
                            }
                        }
                    }
                }

                if ($bestPlan) {
                    $sellPlans[] = $bestPlan;
                }
            }

            if ($kwhToBuy > 0) {
                $buyStrategy = $amberService->calculateOptimalCharging(
                    $kwhToBuy,
                    $batterySettings->longterm_target_electric_price_cents,
                    $batterySettings->longterm_target_price_cents
                );
            }
        }

        $finalSellPlan = $this->mergeSellPlans(...$sellPlans);

        // Cache all the results for polling
        Cache::put('soc', $soc, now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('remaining_solar_generation_today', round($remainingGeneration, 2), now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('forecast_soc', $forecastSoc, now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('kwh_to_buy', $kwhToBuy, now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('buy_strategy', $buyStrategy, now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('sell_strategy', $finalSellPlan, now()->addMinutes(self::CACHE_MINUTES));

        $lowestCurrentSellPrice = $this->getLowestCurrentSellPrice($finalSellPlan, $currentHour);
        $this->updateTargetPrice($batterySettings, $lowestCurrentSellPrice, $soc, $currentHour);

        // Return the fresh data directly
        return response()->json([
            'soc' => $soc,
            'remaining_solar_generation_today' => round($remainingGeneration, 2),
            'forecast_soc' => $forecastSoc,
            'kwh_to_buy' => $kwhToBuy,
            'buyStrategy' => $buyStrategy,
            'sell_strategy' => $finalSellPlan,
            'lowest_current_sell_price' => $lowestCurrentSellPrice,
        ]);
    }

    private function getActiveBatteryStrategies()
    {
        return BatteryStrategy::where('is_active', true)->get();
    }

    private function calculateKwhToSell($soc, $strategy)
    {
        if ($soc > $strategy->soc_lower_bound) {
            $socRange = min($soc, $strategy->soc_upper_bound) - $strategy->soc_lower_bound;
            return $socRange * self::SOC_TO_KWH_FACTOR;
        }

        return 0;
    }

    private function getLowestCurrentSellPrice($sellPlan, $currentHour)
    {
        if (!$sellPlan || !isset($sellPlan['sell_plan'])) {
            return 0;
        }

        $lowestPrice = 0;

        foreach ($sellPlan['sell_plan'] as $slot) {
            $slotHour = Carbon::parse($slot['time'])->hour;
            if ($slotHour >= $currentHour) {
                if ($lowestPrice == 0 || $slot['price'] < $lowestPrice) {
                    $lowestPrice = $slot['price'];
                }
            }
        }

        return $lowestPrice;
    }

    private function updateTargetPrice($batterySettings, $lowestCurrentSellPrice, $soc, $currentHour)
    {
        $strategies = $this->getActiveBatteryStrategies();
        $socHigh = $strategies->firstWhere('name', 'Evening Peak')->soc_lower_bound;
        $socMedium = $strategies->firstWhere('name', 'Flexible Evening')->soc_lower_bound;
        $socLow = $strategies->firstWhere('name', 'Overnight')->soc_lower_bound;

        $lateEveningStartHour = Carbon::parse($strategies->firstWhere('name', 'Flexible Late')->sell_start_time)->hour;
        $lateEveningEndHour = Carbon::parse($strategies->firstWhere('name', 'Flexible Late')->sell_end_time)->hour;
        $lateNightEndHour = Carbon::parse($strategies->firstWhere('name', 'Overnight')->sell_end_time)->hour;

        if (($currentHour > $lateEveningStartHour && $soc > $socHigh) || ($currentHour > $lateEveningEndHour && $soc > $socMedium)) {
            $batterySettings->target_price_cents = $batterySettings->longterm_target_price_cents;
            $batterySettings->save();
        } elseif ($lowestCurrentSellPrice > $batterySettings->longterm_target_price_cents) {
            $batterySettings->target_price_cents = $lowestCurrentSellPrice;
            $batterySettings->save();
        }

        if ($currentHour >= $lateNightEndHour && $currentHour < 3 && $soc > $socLow) {
            $batterySettings->target_price_cents = $batterySettings->longterm_target_price_cents;
            $batterySettings->save();
        }
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
