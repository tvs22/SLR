<?php

namespace App\Http\Controllers;

use App\BatterySoc;
use App\PvYield;
use App\Services\FoxEssService;
use App\SolarForecast;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BatterySocController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $socData = BatterySoc::query();

        if ($request->filled('type')) {
            $socData->where('type', $request->type);
        }

        $socData = $socData->orderBy('hour')->get();

        $allSocData = BatterySoc::get();
        $chartData = $allSocData->groupBy('type')->map(function ($group, $type) {
            if ($type === 'current') {
                return $group->groupBy('hour')->map(function ($hourGroup) {
                    return $hourGroup->sortByDesc('created_at')->first()->soc;
                });
            }
            return $group->pluck('soc', 'hour');
        });

        $today = Carbon::today()->toDateString();
        $pvYield = PvYield::where('date', $today)->pluck('kwh', 'hour');

        $solarProduction = [
            8  => 3.37,
            9  => 6.66,
            10 => 11.30,
            11 => 16.95,
            12 => 23.27,
            13 => 29.51,
            14 => 34.99,
            15 => 39.46,
            16 => 42.75,
            17 => 44.77,
            18 => 45.78,
            19 => 46.00,
        ];

        $pvForecastKwh = collect();
        $lastYieldHour = $pvYield->keys()->last();
        $lastYieldValue = $pvYield->last();

        if ($lastYieldValue !== null && isset($solarProduction[$lastYieldHour]) && $solarProduction[$lastYieldHour] > 0) {
            //$pvForecastKwh = $pvYield->copy();

            foreach($solarProduction as $hour => $production) {
                if ($hour > $lastYieldHour) {
                    $pvForecastKwh[$hour] = round(($lastYieldValue / $solarProduction[$lastYieldHour]) * $production, 2);
                }
            }
        }

        $conversionFactor = 0.4193;

        // SOC Forecast Calculation
        $socForecast = collect();
        $socForecastKwh = collect();
        $currentSoc = $chartData->get('current');
        if ($currentSoc && $currentSoc->isNotEmpty()) {
            $lastCurrentSocHour = $currentSoc->keys()->last();
            $lastCurrentSocValue = $currentSoc->last();

            $pvForecast = $pvForecastKwh->map(fn($kwh) => $kwh / $conversionFactor);
            $pvForecastAtLastSocHour = $pvForecast->get($lastCurrentSocHour, 0);
            $pvForecastKwhAtLastSocHour = $pvForecastKwh->get($lastCurrentSocHour, 0);
            $lastCurrentSocInKwh = $lastCurrentSocValue * $conversionFactor;

            $hours = collect(range(0, 19));
            $hours->each(function ($hour) use ($socForecast, $socForecastKwh, $lastCurrentSocHour, $lastCurrentSocValue, $lastCurrentSocInKwh, $pvForecast, $pvForecastKwh, $pvForecastAtLastSocHour, $pvForecastKwhAtLastSocHour) {
                if ($hour >= $lastCurrentSocHour) {
                    $forecastedIncrease = $pvForecast->get($hour, 0) - $pvForecastAtLastSocHour;
                    $socForecastValue = min(100, $lastCurrentSocValue + $forecastedIncrease);
                    $socForecast->put($hour, $socForecastValue);

                    $forecastedKwhIncrease = $pvForecastKwh->get($hour, 0) - $pvForecastKwhAtLastSocHour;
                    $socForecastKwhValue = $lastCurrentSocInKwh + $forecastedKwhIncrease;
                    $socForecastKwh->put($hour, $socForecastKwhValue);
                }
            });
        }

        $chartData->put('pv_yield', $pvYield->map(fn($kwh) => $kwh / $conversionFactor));
        $chartData->put('pv_yield_kwh', $pvYield);
        $chartData->put('pv_forecast', $pvForecastKwh->map(fn($kwh) => $kwh / $conversionFactor));
        $chartData->put('pv_forecast_kwh', $pvForecastKwh);
        $chartData->put('pv_min_target', collect($solarProduction)->map(fn($prod) => ($prod / 2) / $conversionFactor));
        $chartData->put('pv_min_target_kwh', collect($solarProduction)->map(fn($prod) => $prod/2));
        $chartData->put('pv_max_target', collect($solarProduction)->map(fn($prod) => $prod / $conversionFactor));
        $chartData->put('pv_max_target_kwh', collect($solarProduction));
        $chartData->put('soc_forecast', $socForecast);
        $chartData->put('soc_forecast_kwh', $socForecastKwh);


        return view('battery_soc.index', compact('socData', 'chartData'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        return view('battery_soc.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'hour' => 'required|integer|min:0|max:23',
            'soc' => 'required|integer|min:0|max:100',
            'type' => 'required|string',
        ]);

        BatterySoc::create($validatedData);

        return redirect()->route('battery_soc.index')
                        ->with('success', 'Battery SOC created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\BatterySoc  $batterySoc
     * @return void
     */
    public function show(BatterySoc $batterySoc)
    {
        return view('battery_soc.show', compact('batterySoc'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\BatterySoc  $batterySoc
     * @return void
     */
    public function edit(BatterySoc $batterySoc)
    {
        return view('battery_soc.edit', compact('batterySoc'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\BatterySoc  $batterySoc
     * @return void
     */
    public function update(Request $request, BatterySoc $batterySoc)
    {
        $validatedData = $request->validate([
            'hour' => 'required|integer|min:0|max:23',
            'soc' => 'required|integer|min:0|max:100',
            'type' => 'required|string',
        ]);

        $batterySoc->update($validatedData);

        return redirect()->route('battery_soc.index')
                        ->with('success', 'Battery SOC updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\BatterySoc  $batterySoc
     * @return void
     */
    public function destroy(BatterySoc $batterySoc)
    {
        $batterySoc->delete();

        return redirect()->route('battery_soc.index')
                        ->with('success', 'Battery SOC deleted successfully');
    }

    /**
     * Get the current battery SOC from the FOX ESS API.
     *
     * @return void
     */
    public function getSoc(FoxEssService $foxEssService)
    {
        $errors = [];
        $successes = [];

        // PV Yield
        PvYield::whereDate('created_at', '<', Carbon::today())->delete();
        $now = Carbon::now();
        $currentHour = $now->hour;

        $existingPvYield = PvYield::where('hour', $currentHour)
            ->whereDate('created_at', $now->today())
            ->first();

        if ($existingPvYield) {
            $errors[] = 'A "current" PV yield value for this hour has already been recorded today.';
        } else {
            $reportVars = [
                "gridConsumption",
                "loads",
                "feedin",
                "generation",
                "chargeEnergyToTal",
                "dischargeEnergyToTal",
                "PVEnergyTotal",
            ];
            $data = $foxEssService->getReport("day", $reportVars);
            $totalGeneration = 0;
            if (isset($data['result'])) {
                foreach ($data['result'] as $report) {
                    if ($report['variable'] === 'PVEnergyTotal' && isset($report['values'])) {
                        $totalGeneration = array_sum($report['values']);
                        break;
                    }
                }
            }
            PvYield::create([
                'date' => $now->toDateString(),
                'hour' => $currentHour,
                'kwh' => $totalGeneration,
            ]);
            $successes[] = 'PV yield updated successfully.';
        }

        // Battery SOC
        BatterySoc::where('type', 'current')
            ->whereDate('created_at', '<', Carbon::today())
            ->delete();

        $existingBatterySoc = BatterySoc::where('type', 'current')
            ->where('hour', $currentHour)
            ->whereDate('created_at', $now->today())
            ->first();

        if ($existingBatterySoc) {
            $errors[] = 'A "current" SOC value for this hour has already been recorded today.';
        } else {
            $soc = $foxEssService->getSoc();

            if ($soc !== null) {
                BatterySoc::create([
                    'type' => 'current',
                    'hour' => $currentHour,
                    'soc' => $soc,
                ]);
                $successes[] = 'Battery SOC updated successfully.';
            } else {
                $errors[] = 'Failed to update Battery SOC.';
            }
        }

        $message = '';
        if (count($successes) > 0) {
            $message .= implode(' ', $successes);
        }
        if (count($errors) > 0) {
            $message .= ' ' . implode(' ', $errors);
        }

        return redirect()->route('battery_soc.index')->with(count($errors) > 0 ? 'error' : 'success', $message);
    }
}
