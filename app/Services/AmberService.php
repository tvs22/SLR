<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AmberService
{
    const BATTERY_POWER = 9; // kW

    public function getLatestPrices(): array
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');
        $electricityPrice = null;
        $solarPrice = null;

        if ($apiKey && $siteId && $siteId !== 'your_site_id') {
            try {
                $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=0&previous=0&resolution=5";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    $currentPriceData = collect($data)->firstWhere('type', 'CurrentInterval');

                    if ($currentPriceData && $currentPriceData['channelType'] === 'general') {
                        $electricityPrice = $currentPriceData['perKwh'];
                        $solarPrice = $currentPriceData['spotPerKwh'];
                    }
                } else {
                    Log::error('Error fetching Amber Electric data: HTTP Status ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching Amber Electric data: ' . $e->getMessage());
            }
        }

        return [
            'electricityPrice' => $electricityPrice,
            'solarPrice' => $solarPrice,
        ];
    }

    public function calculateOptimalCharging(float $kwh, float $electricBuyTargetPrice, float $solarTargetSellPrice)
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');

        if (!$apiKey || !$siteId || $siteId === 'your_site_id') {
            return ['error' => 'API key or site ID is not configured.'];
        }

        try {
            $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=48&previous=0&resolution=30"; // Fetching for next 24 hours (48*30min)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::error('Error fetching Amber Electric data: HTTP Status ' . $response->status());
                return ['error' => 'Failed to fetch data from Amber Electric API.'];
            }

            $data = collect($response->json())
                ->where('channelType', 'general')
                ->map(function ($interval) {
                    $interval['startTimeCarbon'] = Carbon::parse($interval['startTime']);
                    return $interval;
                });

            $buyIntervals = $data
                ->filter(function ($interval) use ($electricBuyTargetPrice) {
                    return $interval['perKwh'] <= $electricBuyTargetPrice;
                })
                ->sortBy('perKwh');

            if ($buyIntervals->isEmpty()) {
                return ['message' => 'No profitable buy windows found.'];
            }

            $totalKwhToAcquire = $kwh;
            $buyPlan = [];
            $totalCost = 0;
            $kwhAcquired = 0;

            foreach ($buyIntervals as $interval) {
                if ($kwhAcquired >= $totalKwhToAcquire) {
                    break;
                }

                $kwhPerInterval = self::BATTERY_POWER * (30 / 60);
                $kwhToAcquireInInterval = min($kwhPerInterval, $totalKwhToAcquire - $kwhAcquired);
                $costForInterval = $kwhToAcquireInInterval * $interval['perKwh'];

                $buyPlan[] = [
                    'time' => Carbon::parse($interval['nemTime'])->format('H:i'),
                    'cost' => round($costForInterval, 2),
                    'kwh' => round($kwhToAcquireInInterval, 2),
                    'price' => round($interval['perKwh'], 2),
                ];
                
                $kwhAcquired += $kwhToAcquireInInterval;
                $totalCost += $costForInterval;
            }

            return [
                'buy_plan' => $buyPlan,
                'total_kwh_acquired' => round($kwhAcquired, 2),
                'total_cost' => round($totalCost, 2),
            ];

        } catch (\Exception $e) {
            Log::error('Error in calculateOptimalCharging: ' . $e->getMessage());
            return ['error' => 'An exception occurred: ' . $e->getMessage()];
        }
    }

    public function calculateOptimalDischarging(float $kwh, float $solarTargetSellPrice, Carbon $startTime, Carbon $endTime)
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');

        if (!$apiKey || !$siteId || $siteId === 'your_site_id') {
            return ['error' => 'API key or site ID is not configured.'];
        }

        try {
            $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=48&previous=0&resolution=30";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::error('Error fetching Amber Electric data: HTTP Status ' . $response->status());
                return ['error' => 'Failed to fetch data from Amber Electric API.'];
            }

            $data = collect($response->json())
                ->where('channelType', 'general')
                ->map(function ($interval) {
                    $interval['startTimeCarbon'] = Carbon::parse($interval['startTime']);
                    return $interval;
                })
                ->filter(function ($interval) use ($startTime, $endTime) {
                    $intervalTime = Carbon::parse($interval['nemTime']);
                    return $intervalTime->between($startTime, $endTime);
                });

            $kwhToSell = $kwh;
            $sellPlan = [];
            $kwhSold = 0;
            $totalRevenue = 0;

            while ($kwhSold < $kwhToSell && $solarTargetSellPrice >= 0) {
                $sellIntervals = $data
                    ->filter(function ($interval) use ($solarTargetSellPrice) {
                        return $interval['spotPerKwh'] >= $solarTargetSellPrice;
                    })
                    ->sortByDesc('spotPerKwh');

                if ($sellIntervals->isNotEmpty()) {
                    foreach ($sellIntervals as $interval) {
                        if ($kwhSold >= $kwhToSell) {
                            break;
                        }

                        $kwhPerInterval = self::BATTERY_POWER * (30 / 60);
                        $kwhToSellInInterval = min($kwhPerInterval, $kwhToSell - $kwhSold);

                        if (!collect($sellPlan)->contains('nemTime', $interval['nemTime'])) {
                             $sellPlan[] = [
                                'time' => Carbon::parse($interval['nemTime'])->format('H:i'),
                                'revenue' => round($kwhToSellInInterval * $interval['spotPerKwh'], 2),
                                'kwh' => round($kwhToSellInInterval, 2),
                                'price' => round($interval['spotPerKwh'], 2),
                            ];
                            $kwhSold += $kwhToSellInInterval;
                            $totalRevenue += $kwhToSellInInterval * $interval['spotPerKwh'];
                        }
                    }
                }
                $solarTargetSellPrice -= 1; // Reduce by 1 cent
            }


            if (empty($sellPlan)) {
                return ['message' => 'No profitable selling opportunities found within the time window.'];
            }
            
            $sellPlanCollection = collect($sellPlan);

            return [
                'sell_plan' => $sellPlan,
                'total_kwh_sold' => round($kwhSold, 2),
                'total_revenue' => round($totalRevenue, 2),
                'highest_sell_price' => $sellPlanCollection->max('price'),
                'lowest_sell_price' => $sellPlanCollection->min('price'),
                'highest_sell_price_time' => $sellPlanCollection->sortByDesc('price')->first()['time'],
            ];

        } catch (\Exception $e) {
            Log::error('Error in calculateOptimalDischarging: ' . $e->getMessage());
            return ['error' => 'An exception occurred: ' . $e->getMessage()];
        }
    }
}
