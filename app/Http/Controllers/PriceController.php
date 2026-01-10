<?php

namespace App\Http\Controllers;

use App\Models\BatteryStrategy;
use App\Services\AmberService;
use App\Services\FoxEssService;
use App\SolarForecast;
use App\BatterySetting;
use App\Models\SellPlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PriceController extends Controller
{
    private const SOC_TO_KWH_FACTOR = 0.4193;
    private const CACHE_MINUTES = 30;

    public function getPredictedPrices(AmberService $amberService, FoxEssService $foxEssService): JsonResponse
    {
        $now = Carbon::now();
        $batterySettings = BatterySetting::latest()->first();
        $soc = $foxEssService->getSoc();
        $sellPlans = [];
        $buyStrategy = ['everyday_buy_plan' => [], 'essential_buy_plan' => [], 'target_buy_plan' => []];

        if ($soc !== null && $batterySettings) {
            $batteryStrategies = $this->getActiveBatteryStrategies();
            $sellPlans = $this->calculateSellPlans($soc, $now, $amberService, $batterySettings, $batteryStrategies);
            $buyStrategy = $this->pvYieldBackfill($soc, $amberService, $batterySettings, $foxEssService);

            $finalSellPlan = $this->mergeSellPlans(...array_values($sellPlans));
            $this->saveSellPlanHistory($finalSellPlan);

            // Update battery settings in memory
            $this->updateBatteryStatus($batterySettings, $finalSellPlan, $buyStrategy, $now);
            //$this->updateTargetElectricPrice($batterySettings, ...array_values($buyStrategy));
            $lowestCurrentSellPrice = $this->getLowestCurrentSellPrice($finalSellPlan, $now->hour);
            $this->updateTargetPrice($batterySettings, $lowestCurrentSellPrice, $soc, $now->hour, $batteryStrategies);

            // Save all changes to the database at once
            $batterySettings->save();

            $this->cacheResults($soc, $buyStrategy, $sellPlans, $lowestCurrentSellPrice);

        } else {
             $lowestCurrentSellPrice = 0; // Default value if SOC or settings are unavailable
        }

        return response()->json([
            'everyday_buy_plan' => $buyStrategy['everyday_buy_plan'],
            'essential_buy_plan' => $buyStrategy['essential_buy_plan'],
            'target_buy_plan' => $buyStrategy['target_buy_plan'],
            'kwh_to_buy_essential' => $buyStrategy['kwh_to_buy_essential'] ?? 0,
            'kwh_to_buy_target' => $buyStrategy['kwh_to_buy_target'] ?? 0,
            'evening_sell_strategy' => $sellPlans['Evening Peak'] ?? null,
            'late_evening_sell_strategy' => $sellPlans['Flexible Evening'] ?? null,
            'late_night_sell_strategy' => $sellPlans['Overnight'] ?? null,
            'lowest_current_sell_price' => $lowestCurrentSellPrice,
        ]);
    }

    public function simulation(Request $request, AmberService $amberService): JsonResponse
    {
        if ($request->isMethod('get') && !$request->hasAny(['soc', 'time'])) {
            return view('price.simulation');
        }

        try {
            $validator = Validator::make($request->all(), [
                'soc' => 'required|numeric|min:0|max:100',
                'time' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Invalid input', 'errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
            $soc = $validated['soc'];
            $time = Carbon::parse($validated['time']);

            $batterySettings = BatterySetting::latest()->first();
            if (!$batterySettings) {
                return response()->json(['message' => 'Battery settings have not been configured.'], 404);
            }

            $batteryStrategies = $this->getActiveBatteryStrategies();
            $sellPlans = $this->calculateSellPlans($soc, $time, $amberService, $batterySettings, $batteryStrategies);
            $finalSellPlan = $this->mergeSellPlans(...array_values($sellPlans));

            return response()->json($finalSellPlan);

        } catch (\Throwable $e) {
            Log::error('Simulation Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'An unexpected server error occurred during the simulation.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateSellPlans(float $soc, Carbon $now, AmberService $amberService, BatterySetting $batterySettings, $batteryStrategies): array
    {
        $allPotentialPlans = [];
        $allocatedSlots = [];

        // First, calculate a potential plan for every active strategy
        foreach ($batteryStrategies as $strategy) {
            $kwhToSell = $this->calculateKwhToSell($soc, $strategy);
            $plan = null;

            if ($kwhToSell > 0) {
                $startTime = Carbon::parse($strategy->sell_start_time);
                $endTime = Carbon::parse($strategy->sell_end_time);
                if ($endTime->lt($startTime)) {
                    $endTime->addDay();
                }

                $effectiveStart = $now->max($startTime);

                if ($effectiveStart < $endTime) {
                    $plan = $amberService->calculateOptimalDischarging(
                        $kwhToSell,
                        $batterySettings->longterm_target_price_cents,
                        $effectiveStart,
                        $endTime,
                        $allocatedSlots
                    );

                    if (isset($plan['sell_plan'])) {
                        foreach ($plan['sell_plan'] as $slot) {
                            $allocatedSlots[] = $slot;
                        }
                    }
                }
            }
            $allPotentialPlans[$strategy->name] = $plan;
        }

        $finalSellPlans = [];

        // Now, iterate through the groups and decide which plan to use
        foreach ($batteryStrategies->groupBy('strategy_group') as $group => $strategies) {
            if (empty($group)) {
                // For strategies without a group, just add their plan
                foreach ($strategies as $strategy) {
                    $finalSellPlans[$strategy->name] = $allPotentialPlans[$strategy->name];
                }
            } else {
                // For strategies in a group, find the best one
                $bestPlan = null;
                $bestPlanRevenue = -1;
                $bestStrategyName = null;

                foreach ($strategies as $strategy) {
                    $plan = $allPotentialPlans[$strategy->name];
                    if (isset($plan['total_revenue']) && $plan['total_revenue'] > $bestPlanRevenue) {
                        $bestPlan = $plan;
                        $bestPlanRevenue = $plan['total_revenue'];
                        $bestStrategyName = $strategy->name;
                    }
                }

                // Add the best plan to the final list
                if ($bestStrategyName) {
                    $finalSellPlans[$bestStrategyName] = $bestPlan;
                }

                // For all other strategies in the group, ensure their plan is null
                foreach ($strategies as $strategy) {
                    if ($strategy->name !== $bestStrategyName) {
                        $finalSellPlans[$strategy->name] = null;
                    }
                }
            }
        }

        return $finalSellPlans;
    }

    private function updateBatteryStatus(BatterySetting $batterySettings, ?array $finalSellPlan, array $buyStrategy, Carbon $now): void
    {
        $batteryStrategies = $this->getActiveBatteryStrategies();

        // Sell Window Logic
        $hasActiveSellPlan = !empty($finalSellPlan['sell_plan']);
        $isWithinSellWindow = false;
        foreach ($batteryStrategies as $strategy) {
            if ($strategy->sell_start_time && $strategy->sell_end_time) {
                $startTime = Carbon::parse($strategy->sell_start_time);
                $endTime = Carbon::parse($strategy->sell_end_time);
                if ($endTime->lt($startTime)) {
                    $endTime->addDay();
                }
                if ($now->between($startTime, $endTime)) {
                    $isWithinSellWindow = true;
                    break;
                }
            }
        }

        // Buy Window Logic
        $eveningPeakStrategy = $batteryStrategies->firstWhere('name', 'Evening Peak');
        $isWithinBuyWindow = false;
        if ($eveningPeakStrategy && $eveningPeakStrategy->buy_start_time && $eveningPeakStrategy->buy_end_time) {
            $buyStartTime = Carbon::parse($eveningPeakStrategy->buy_start_time);
            $buyEndTime = Carbon::parse($eveningPeakStrategy->buy_end_time);
            if ($buyEndTime->lt($buyStartTime)) {
                $buyEndTime->addDay();
            }
            $isWithinBuyWindow = $now->between($buyStartTime, $buyEndTime);
        }

        $hasActiveBuyPlan = !empty($buyStrategy['everyday_buy_plan']['buy_plan'])
            || !empty($buyStrategy['essential_buy_plan']['buy_plan'])
            || !empty($buyStrategy['target_buy_plan']['buy_plan']);

        // Determine Status
        $newStatus = 'self_sufficient'; // Default status

        if ($hasActiveSellPlan && $isWithinSellWindow) {
            $newStatus = 'prioritize_selling';
        } elseif ($hasActiveBuyPlan && $isWithinBuyWindow) {
            $newStatus = 'prioritize_charging';
        }

        if ($batterySettings->status !== $newStatus) {
            $batterySettings->status = $newStatus;
        }
    }

    private function cacheResults($soc, array $buyStrategy, array $sellPlans, float $lowestCurrentSellPrice): void
    {
        Cache::put('soc', $soc, now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('remaining_solar_generation_today', $buyStrategy['future_generation_kwh'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('forecast_soc', $soc + $buyStrategy['future_generation_percent'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('everyday_buy_plan', $buyStrategy['everyday_buy_plan'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('essential_buy_plan', $buyStrategy['essential_buy_plan'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('target_buy_plan', $buyStrategy['target_buy_plan'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('kwh_to_buy_essential', $buyStrategy['kwh_to_buy_essential'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('kwh_to_buy_target', $buyStrategy['kwh_to_buy_target'], now()->addMinutes(self::CACHE_MINUTES));
        Cache::put('lowest_current_sell_price', $lowestCurrentSellPrice, now()->addMinutes(self::CACHE_MINUTES));

        $keyMap = [
            'Evening Peak' => 'evening_sell_strategy',
            'Flexible Evening' => 'late_evening_sell_strategy',
            'Overnight' => 'late_night_sell_strategy',
        ];

        foreach ($sellPlans as $name => $plan) {
            if (isset($keyMap[$name])) {
                Cache::put($keyMap[$name], $plan, now()->addMinutes(self::CACHE_MINUTES));
            }
        }
    }

    public function pvYieldBackfill($soc, AmberService $amberService, BatterySetting $batterySettings, FoxEssService $foxEssService): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentHour = $now->hour;

        $eveningPeakStrategy = BatteryStrategy::where('name', 'Evening Peak')->first();
        $buyStartTime = Carbon::parse($eveningPeakStrategy->buy_start_time);
        $buyEndTime = Carbon::parse($eveningPeakStrategy->buy_end_time);

        $currentYield = DB::table('pv_yields')->where('date', $today)->where('hour', $currentHour)->first();

        $allocatedSlots = [];
        $everydayBuyPlan = ['buy_plan' => []];
        $essentialBuyPlan = ['buy_plan' => []];
        $targetBuyPlan = ['buy_plan' => []];
        $futureGenerationKwh = 0;
        $futureGenerationPercent = 0;
        $kwhToBuyEssential = 0;
        $kwhToBuyTarget = 0;

        $reportVars = ["gridConsumption"];
        $data = $foxEssService->getReport("day", $reportVars);
        $gridConsumption = 0;

        if (isset($data['result'])) {
            foreach ($data['result'] as $report) {
                if ($report['variable'] === 'gridConsumption' && isset($report['values'])) {
                    $gridConsumption = array_sum($report['values']);
                    break;
                }
            }
        }

        $gridConsumption = round($gridConsumption, 2);

        if ($gridConsumption < 14) {
            $everydayBuyPlan = $amberService->calculateOptimalCharging(
                14 - $gridConsumption,
                $batterySettings->longterm_target_electric_price_cents,
                $buyStartTime,
                $buyEndTime,
                $allocatedSlots
            );
            if (isset($everydayBuyPlan['buy_plan'])) {
                foreach ($everydayBuyPlan['buy_plan'] as $slot) {
                    $allocatedSlots[] = $slot;
                }
            }
        }

        if ($currentYield) {
            $currentYieldKwh = $currentYield->kwh;
            $solarProduction = [
                8 => 2.78, 9 => 5.5, 10 => 9.34, 11 => 14, 12 => 19.23,
                13 => 24.39, 14 => 28.9, 15 => 33.7, 16 => 38.2, 17 => 42,
                18 => 44.5, 19 => 46.00,
            ];

            if (isset($solarProduction[$currentHour]) && $solarProduction[$currentHour] > 0) {
                $targetKwh = end($solarProduction);
                $futureGenerationKwh = (($currentYieldKwh / $solarProduction[$currentHour]) * $targetKwh) - $currentYieldKwh;
                $futureGenerationPercent = round($futureGenerationKwh / self::SOC_TO_KWH_FACTOR);
                $socInKwh = $soc * self::SOC_TO_KWH_FACTOR;
                $essentialKwhTarget = 20;

                $kwhToBuyEssential = max(0, $essentialKwhTarget - $futureGenerationKwh - $socInKwh);
                if ($kwhToBuyEssential > 0) {
                    $essentialBuyPlan = $amberService->calculateOptimalCharging(
                        $kwhToBuyEssential,
                        $batterySettings->longterm_target_electric_price_cents,
                        $buyStartTime,
                        $buyEndTime,
                        $allocatedSlots
                    );
                    if (isset($essentialBuyPlan['buy_plan'])) {
                        foreach ($essentialBuyPlan['buy_plan'] as $slot) {
                            $allocatedSlots[] = $slot;
                        }
                    }
                }

                $kwhToBuyTarget = max(0, $targetKwh - $futureGenerationKwh - $socInKwh - $kwhToBuyEssential);
                if ($kwhToBuyTarget > 0) {
                    $targetBuyPlan = $amberService->calculateOptimalCharging(
                        $kwhToBuyTarget,
                        $batterySettings->longterm_target_electric_price_cents,
                        $buyStartTime,
                        $buyEndTime,
                        $allocatedSlots
                    );
                }
            }
        }

        return [
            'everyday_buy_plan' => $everydayBuyPlan,
            'essential_buy_plan' => $essentialBuyPlan,
            'target_buy_plan' => $targetBuyPlan,
            'future_generation_kwh' => round($futureGenerationKwh, 2),
            'future_generation_percent' => $futureGenerationPercent,
            'kwh_to_buy_essential' => round($kwhToBuyEssential, 2),
            'kwh_to_buy_target' => round($kwhToBuyTarget, 2),
        ];
    }

    private function getActiveBatteryStrategies()
    {
        return BatteryStrategy::where('is_active', true)->orderBy('id', 'asc')->get();
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

    private function updateTargetPrice($batterySettings, $lowestCurrentSellPrice, $soc, $currentHour, $strategies)
    {
        if (!$batterySettings) return;

        $eveningPeakStrategy = $strategies->firstWhere('name', 'Evening Peak');
        $flexibleEveningStrategy = $strategies->firstWhere('name', 'Flexible Evening');
        $overnightStrategy = $strategies->firstWhere('name', 'Overnight');

        if (!$eveningPeakStrategy || !$flexibleEveningStrategy || !$overnightStrategy) {
            // One or more strategies are not found, so we can't proceed.
            // Log this event for debugging purposes.
            Log::warning('updateTargetPrice: One or more battery strategies are missing.');
            return;
        }

        $eveningPeakEndHour = Carbon::parse($eveningPeakStrategy->sell_end_time)->hour;
        $lateEveningEndHour = Carbon::parse($flexibleEveningStrategy->sell_end_time)->hour;
        $lateNightEndHour = Carbon::parse($overnightStrategy->sell_end_time)->hour;

        $socHigh = $eveningPeakStrategy->soc_lower_bound;
        $socMedium = $flexibleEveningStrategy->soc_lower_bound;
        $socLow = $overnightStrategy->soc_lower_bound;

        $newTargetPrice = null;

        if ($currentHour >= $eveningPeakEndHour && $currentHour < $lateEveningEndHour && $soc > $socHigh) {
            $newTargetPrice = $batterySettings->longterm_target_price_cents;
        } elseif ($currentHour >= $lateEveningEndHour && $soc > $socMedium) {
            $newTargetPrice = $batterySettings->longterm_target_price_cents;
        } elseif ($currentHour >= $lateNightEndHour && $currentHour < 3 && $soc > $socLow) {
            $newTargetPrice = $batterySettings->longterm_target_price_cents;
        } elseif ($lowestCurrentSellPrice > $batterySettings->longterm_target_price_cents) {
            $newTargetPrice = $lowestCurrentSellPrice;
        }

        if ($newTargetPrice !== null && $newTargetPrice != $batterySettings->target_price_cents) {
            $newTargetPrice = round($newTargetPrice * 0.9, 2);
            $batterySettings->target_price_cents = $newTargetPrice;
            Cache::forget('offer_price');
            Cache::forget('sell_score');
            Cache::forget('threshold');
            Cache::forget('solar_prices');
            Cache::forget('forecast_errors');
        }
    }

    private function updateTargetElectricPrice(BatterySetting $batterySettings, ?array ...$buyPlans): void
    {
        $highestBuyPrice = 0;

        foreach ($buyPlans as $plan) {
            if (is_array($plan) && isset($plan['buy_plan']) && is_array($plan['buy_plan'])) {
                foreach ($plan['buy_plan'] as $slot) {
                    if (isset($slot['price']) && $slot['price'] > $highestBuyPrice) {
                        $highestBuyPrice = $slot['price'];
                    }
                }
            }
        }

        if ($highestBuyPrice > 0 && $highestBuyPrice < $batterySettings->longterm_target_electric_price_cents) {
            $batterySettings->target_electric_price_cents = round($highestBuyPrice, 2);
        }
    }

    private function mergeSellPlans(...$plans): ?array
    {
        $nonNullPlans = array_filter($plans, function ($plan) {
            return !empty($plan) && empty($plan['error']) && !empty($plan['sell_plan']);
        });

        if (empty($nonNullPlans)) {
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

        usort($mergedPlan['sell_plan'], function ($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return $mergedPlan;
    }

    private function saveSellPlanHistory(?array $sellPlan): void
    {
        if (empty($sellPlan) || empty($sellPlan['sell_plan'])) {
            return;
        }

        foreach ($sellPlan['sell_plan'] as $slot) {
            SellPlan::create([
                'time' => Carbon::parse($slot['time']),
                'revenue' => $slot['revenue'],
                'kwh' => $slot['kwh'],
                'price' => $slot['price'],
            ]);
        }
    }
}
