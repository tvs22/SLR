<?php

namespace App\Http\Controllers;

use App\Services\FoxEssService;
use App\SolarForecast;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PriceController extends Controller
{
    /**
     * Get the estimated solar forecast for the current time.
     *
     * @param FoxEssService $foxEssService
     * @return JsonResponse
     */
    public function getEstimatedSolarForecast(FoxEssService $foxEssService): JsonResponse
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentHour = $now->hour;
        $minute = $now->minute;

        $forecastsToday = SolarForecast::where('date', $today)->orderBy('hour', 'asc')->get();

        if ($forecastsToday->isEmpty()) {
            $soc = $foxEssService->getSoc();
            return response()->json([
                'soc' => $soc,
                'remaining_solar_generation_today' => 0,
                'forecast_soc' => $soc,
            ], 404);
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

        if ($soc !== null) {
            $forecastSoc = $soc + ($remainingGeneration / 0.4193);
            $forecastSoc = min(100, round($forecastSoc));
        }

        return response()->json([
            'soc' => $soc,
            'remaining_solar_generation_today' => round($remainingGeneration, 2),
            'forecast_soc' => $forecastSoc,
        ]);
    }
}
