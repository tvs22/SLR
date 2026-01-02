<?php

namespace App\Http\Controllers;

use App\SolarForecast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SolarForecastController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $solarForecasts = SolarForecast::all();
        return view('solar_forecasts.index', compact('solarForecasts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('solar_forecasts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'hour' => 'required|integer',
            'kwh' => 'required|numeric',
        ]);

        SolarForecast::create($request->all());

        return redirect()->route('solar-forecasts.index')
            ->with('success', 'Solar forecast created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\SolarForecast  $solarForecast
     * @return \Illuminate\Http\Response
     */
    public function show(SolarForecast $solarForecast)
    {
        return view('solar_forecasts.show', compact('solarForecast'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SolarForecast  $solarForecast
     * @return \Illuminate\Http\Response
     */
    public function edit(SolarForecast $solarForecast)
    {
        return view('solar_forecasts.edit', compact('solarForecast'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SolarForecast  $solarForecast
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SolarForecast $solarForecast)
    {
        $request->validate([
            'date' => 'required|date',
            'hour' => 'required|integer',
            'kwh' => 'required|numeric',
        ]);

        $solarForecast->update($request->all());

        return redirect()->route('solar-forecasts.index')
            ->with('success', 'Solar forecast updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SolarForecast  $solarForecast
     * @return \Illuminate\Http\Response
     */
    public function destroy(SolarForecast $solarForecast)
    {
        $solarForecast->delete();

        return redirect()->route('solar-forecasts.index')
            ->with('success', 'Solar forecast deleted successfully');
    }

    /**
     * Fetches solar forecast data from the forecast.solar API and updates the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getSolarForecasts()
    {
        // Delete all forecasts not created today
        SolarForecast::whereDate('created_at', '<', Carbon::today())->delete();
        // Check if today's forecast already exists
        $todaysForecastExists = SolarForecast::whereDate('date', Carbon::today())->exists();

        if ($todaysForecastExists) {
            return redirect()->route('solar-forecasts.index')->with('error', 'Solar forecast for today has already been updated.');
        }

        $response = Http::get('https://api.forecast.solar/estimate/watthours/-33.8068538/150.6820298/37/100/10');

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['result'])) {
                $result = $data['result'];

                foreach ($result as $timestamp => $wattHours) {
                    $datetime = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Australia/Sydney');

                    SolarForecast::create([
                        'date' => $datetime->toDateString(),
                        'hour' => $datetime->hour,
                        'kwh' => $wattHours / 1000,
                    ]);
                }
                return redirect()->route('solar-forecasts.index')->with('success', 'Solar forecast updated successfully.');
            }
        }

        return redirect()->route('solar-forecasts.index')->with('error', 'Could not fetch solar forecast data.');
    }

    /**
     * Remove all resources from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteAll()
    {
        SolarForecast::truncate();

        return redirect()->route('solar-forecasts.index')
            ->with('success', 'All solar forecasts deleted successfully.');
    }
}
