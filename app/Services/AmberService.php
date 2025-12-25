<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AmberService
{
    const BATTERY_POWER = 9; // kW
    const INTERVAL_DURATION = 5; // minutes

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

    public function predicted_prices(int $kwh, $status = 'BuyKWH', $start_hour = null, $end_hour = null)
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');

        if (!$apiKey || !$siteId || $siteId === 'your_site_id') {
            return ['error' => 'API key or site ID is not configured.'];
        }

        $priceField = ($status === 'BuyKWH') ? 'perKwh' : 'spotPerKwh';
        $sortDirectionIsAsc = ($status === 'BuyKWH');
        $now = Carbon::now();

        $start_datetime = null;
        $end_datetime = null;
        $next = ($status === 'BuyKWH') ? 36 : 60; // Default intervals

        if ($start_hour !== null && $end_hour !== null) {
            $start_datetime = Carbon::createFromTimeString($start_hour . ':00:00');
            $end_datetime = Carbon::createFromTimeString($end_hour . ':00:00');

            if ($start_datetime >= $end_datetime) { // Overnight window
                $end_datetime->addDay();
            }

            if ($now > $end_datetime) { // If window has passed for today, move to next day
                $start_datetime->addDay();
                $end_datetime->addDay();
            }
            
            // If we are already in the window, start from now
            if($now > $start_datetime) {
                $start_datetime = $now;
            }

            $minutesUntilEnd = $now->diffInMinutes($end_datetime, false);
            if ($minutesUntilEnd > 0) {
                $next = ceil($minutesUntilEnd / self::INTERVAL_DURATION) + 1; // Calculate intervals needed
            } else {
                $next = 0;
            }
        }

        if ($next <= 0) {
            return ['error' => 'The specified time window is in the past or too short.'];
        }

        try {
            $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next={$next}&previous=0&resolution=5";
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
                    $interval['hour'] = $interval['startTimeCarbon']->format('Y-m-d H');
                    return $interval;
                });

            if ($start_datetime && $end_datetime) {
                $data = $data->filter(function ($interval) use ($start_datetime, $end_datetime) {
                    return $interval['startTimeCarbon']->between($start_datetime, $end_datetime, true);
                });
            }
            
            if ($data->isEmpty()) {
                return ['error' => 'No pricing data available for the selected window.'];
            }
            $sortedForInitialPrice = $sortDirectionIsAsc ? $data->sortBy($priceField) : $data->sortByDesc($priceField);
            $initialBestPrice = $sortedForInitialPrice->first()[$priceField];
            $priceLimit = $initialBestPrice;
            $finalSelection = [];
            $finalKwh = 0;
            $maxPriceAdjustment = 0.50;
            $priceAdjustmentStep = 0.01;

            while ($finalKwh < $kwh && abs($priceLimit - $initialBestPrice) <= $maxPriceAdjustment) {
                $potentialIntervals = $data->filter(function ($interval) use ($priceLimit, $priceField, $sortDirectionIsAsc) {
                    return $sortDirectionIsAsc ? $interval[$priceField] <= $priceLimit : $interval[$priceField] >= $priceLimit;
                });

                $sortedPotentials = $sortDirectionIsAsc ? $potentialIntervals->sortBy($priceField) : $potentialIntervals->sortByDesc($priceField);
                
                $currentSelection = [];
                $hourlyKwh = [];
                $accumulatedKwh = 0;
                $intervalsNeeded = ceil($kwh / 0.75);

                foreach ($sortedPotentials as $interval) {
                    if ($accumulatedKwh >= $kwh) {
                        break;
                    }

                    $hour = $interval['hour'];
                    if (!isset($hourlyKwh[$hour])) {
                        $hourlyKwh[$hour] = 0;
                    }

                    if ($hourlyKwh[$hour] < self::BATTERY_POWER) {
                        $currentSelection[] = $interval;
                        $hourlyKwh[$hour] += 0.75;
                        $accumulatedKwh += 0.75;
                    }
                }
                
                $finalKwh = $accumulatedKwh;

                if ($finalKwh >= $kwh) {
                    $finalSelection = $currentSelection;
                    break; 
                }

                $priceLimit += $sortDirectionIsAsc ? $priceAdjustmentStep : -$priceAdjustmentStep;
            }

            $numIntervals = count($finalSelection);
            $totalKwhPlanned = $numIntervals * 0.75;
            
            $finalPriceUsed = 0;
            if($numIntervals > 0) {
                 $finalPriceUsed = $sortDirectionIsAsc ? collect($finalSelection)->max($priceField) : collect($finalSelection)->min($priceField);
            }

            return [
                'status' => ($status === 'BuyKWH') ? 'Buy' : 'Sell',
                'final_price_used' => $finalPriceUsed,
                'total_kwh_planned' => $totalKwhPlanned > $kwh ? $kwh : $totalKwhPlanned,
                'number_of_intervals_selected' => $numIntervals,
            ];

        } catch (\Exception $e) {
            Log::error('Error in predicted_prices: ' . $e->getMessage());
            return ['error' => 'An exception occurred: ' . $e->getMessage()];
        }
    }
}
