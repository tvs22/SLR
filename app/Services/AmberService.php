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

    public function calculateOptimalCharging(int $kwh, float $electricBuyTargetPrice, float $solarTargetSellPrice)
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');
        $conProfit = -1000;

        if (!$apiKey || !$siteId || $siteId === 'your_site_id') {
            return ['error' => 'API key or site ID is not configured.'];
        }

        try {
            // Fetch price forecasts
            $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=72&previous=0&resolution=30"; // Fetching for next 36 hours (72*30min) to be safe
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
                    $time = Carbon::parse($interval['nemTime']);
            
                    return $interval['perKwh'] <= $electricBuyTargetPrice
                        && $time->between(
                            $time->copy()->setTime(11, 0),
                            $time->copy()->setTime(14, 0)
                        );
                })
                ->sortBy('perKwh');

                $sellIntervals = $data
                ->filter(function ($interval) use ($solarTargetSellPrice) {
                    $hour = Carbon::parse($interval['nemTime'])->hour;
            
                    return $interval['spotPerKwh'] >= $solarTargetSellPrice
                        && $hour >= 16
                        && $hour < 22;
                })
                ->sortByDesc('spotPerKwh');
            if ($buyIntervals->isEmpty() || $sellIntervals->isEmpty()) {
                return ['message' => 'No profitable buy or sell windows found.'];
            }

            $totalKwhAcquired = 0;
            $buyPlan = [];
            $totalCost = 0;

            $bestSellPrice = $sellIntervals->first()['spotPerKwh'];

            // Increase buy target to capture more volume if profitable
            $potentialProfit = ($bestSellPrice - $buyIntervals->first()['perKwh']) * $kwh;
            if ($potentialProfit >= $conProfit) {
                $electricBuyTargetPrice += 1; // increase by 1 cent
                 $buyIntervals = $data->filter(function ($interval) use ($electricBuyTargetPrice) {
                    return $interval['perKwh'] <= $electricBuyTargetPrice;
                })->sortBy('perKwh');

            }


            foreach ($buyIntervals as $interval) {
                if ($totalKwhAcquired >= $kwh) {
                    break;
                }

                $kwhPerInterval = self::BATTERY_POWER * (30 / 60);
                $buyPlan[] = $interval;
                $totalKwhAcquired += $kwhPerInterval;
                $totalCost += $kwhPerInterval * $interval['perKwh'];
            }

            $totalRevenue = 0;
            $sellPlan = [];
            $kwhSold = 0;

            foreach ($sellIntervals as $interval) {
                if ($kwhSold >= $totalKwhAcquired) {
                    break;
                }
                $kwhPerInterval = self::BATTERY_POWER * (30 / 60);
                $sellPlan[] = $interval;
                $kwhSold += $kwhPerInterval;
                $totalRevenue += $kwhPerInterval * $interval['spotPerKwh'];
            }

            $profit = $totalRevenue - $totalCost;

            if ($profit < $conProfit) {
                 return ['message' => 'No profitable opportunity found.'];
            }

            $highestBuyPrice = !empty($buyPlan) ? collect($buyPlan)->max('perKwh') : 0;
            $lowestSellPrice = !empty($sellPlan) ? collect($sellPlan)->min('spotPerKwh') : 0;

            return [
                'total_kwh_acquired' => $totalKwhAcquired,
                'total_cost' => $totalCost,
                'total_revenue' => $totalRevenue,
                'estimated_profit' => $profit,
                'highest_buy_price' => $highestBuyPrice,
                'lowest_sell_price' => $lowestSellPrice,
            ];

        } catch (\Exception $e) {
            Log::error('Error in calculateOptimalCharging: ' . $e->getMessage());
            return ['error' => 'An exception occurred: ' . $e->getMessage()];
        }
    }

    public function calculateOptimalDischarging(int $kwh, float $solarTargetSellPrice)
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');

        if (!$apiKey || !$siteId || $siteId === 'your_site_id') {
            return ['error' => 'API key or site ID is not configured.'];
        }

        try {
            // Fetch price forecasts
            $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=72&previous=0&resolution=30";
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

            $sellPlan = [];
            $kwhSold = 0;
            $totalRevenue = 0;
            $adjustedSolarTargetSellPrice = $solarTargetSellPrice;

            // Loop until we have a sell plan or the target price is too low
            while (empty($sellPlan) && $adjustedSolarTargetSellPrice > 0) {
                $sellIntervals = $data
                    ->filter(function ($interval) use ($adjustedSolarTargetSellPrice) {
                        $hour = Carbon::parse($interval['nemTime'])->hour;
                        return $interval['spotPerKwh'] >= $adjustedSolarTargetSellPrice
                            && $hour >= 16
                            && $hour < 22;
                    })
                    ->sortByDesc('spotPerKwh');

                if ($sellIntervals->isEmpty()) {
                    // Reduce the target price by 1 cent and try again
                    $adjustedSolarTargetSellPrice -= 1;
                } else {
                    foreach ($sellIntervals as $interval) {
                        if ($kwhSold >= $kwh) {
                            break;
                        }
                        $kwhPerInterval = self::BATTERY_POWER * (30 / 60);
                        // Ensure we don't sell more than the available kWh
                        $kwhToSell = min($kwhPerInterval, $kwh - $kwhSold);
                        
                        $sellPlan[] = $interval;
                        $kwhSold += $kwhToSell;
                        $totalRevenue += $kwhToSell * $interval['spotPerKwh'];
                    }
                }
            }

            if (empty($sellPlan)) {
                return ['message' => 'No profitable selling opportunities found within the time window.'];
            }

            $highestSellPrice = !empty($sellPlan) ? collect($sellPlan)->max('spotPerKwh') : 0;
            $lowestSellPrice = !empty($sellPlan) ? collect($sellPlan)->min('spotPerKwh') : 0;

            return [
                'total_kwh_sold' => $kwhSold,
                'total_revenue' => $totalRevenue,
                'highest_sell_price' => $highestSellPrice,
                'lowest_sell_price' => $lowestSellPrice,
            ];

        } catch (\Exception $e) {
            Log::error('Error in calculateOptimalDischarging: ' . $e->getMessage());
            return ['error' => 'An exception occurred: ' . $e->getMessage()];
        }
    }
}
