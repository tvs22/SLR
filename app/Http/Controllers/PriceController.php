<?php

namespace App\Http\Controllers;

use App\Services\AmberService;
use App\Services\FoxEssService;
use App\SolarForecast;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PriceController extends Controller
{
    public function getPredictedPrices(AmberService $amberService, FoxEssService $foxEssService): JsonResponse
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentHour = $now->hour;
        $minute = $now->minute;

        $forecastsToday = SolarForecast::where('date', $today)->orderBy('hour', 'asc')->get();

        if ($forecastsToday->isEmpty()) {
            //$soc = $foxEssService->getSoc();
            $soc= 52;
            $kwhToBuy = round((100 - $soc) * 0.4193, 2);
            $predictedPrices = $amberService->predicted_prices(intval($kwhToBuy),'BuyKWH',2,6);

            return response()->json([
                'soc' => $soc,
                'remaining_solar_generation_today' => 0,
                'forecast_soc' => $soc,
                'kwh_to_buy' => $kwhToBuy,
                'predicted_prices' => $predictedPrices,
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

        //$soc = $foxEssService->getSoc();
        $soc=52;
        $forecastSoc = null;
        $kwhToBuy = null;

        if ($soc !== null) {
            $forecastSoc = $soc + ($remainingGeneration / 0.4193);
            $forecastSoc = min(100, round($forecastSoc));

            $gapSoc = 100 - $forecastSoc;
            $kwhToBuy = round($gapSoc * 0.4193, 2);
        }

        $predictedPrices = $amberService->predicted_prices(intval($kwhToBuy),'SellKWH');

        return response()->json([
            'soc' => $soc,
            'remaining_solar_generation_today' => round($remainingGeneration, 2),
            'forecast_soc' => $forecastSoc,
            'kwh_to_buy' => $kwhToBuy,
            'predicted_prices' => $predictedPrices,
        ]);
    }
}
