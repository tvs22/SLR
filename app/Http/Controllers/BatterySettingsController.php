<?php

namespace App\Http\Controllers;

use App\BatterySetting;
use Illuminate\Http\Request;

class BatterySettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = BatterySetting::all();
        return view('battery_settings.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('battery_settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_price_cents' => 'required|numeric',
            'forced_discharge' => 'required|boolean',
            'discharge_start_time' => 'required',
            'target_electric_price_cents' => 'required|numeric',
            'forced_charge' => 'required|boolean',
            'charge_start_time' => 'required',
            'battery_level_percent' => 'required|numeric|min:0|max:100',
        ]);

        BatterySetting::create($validated);

        return redirect()->route('battery-settings.index')
            ->with('success', 'Battery setting created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BatterySetting $batterySetting)
    {
        return view('battery_settings.edit', compact('batterySetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BatterySetting $batterySetting)
    {
        $validated = $request->validate([
            'target_price_cents' => 'required|numeric',
            'forced_discharge' => 'required|boolean',
            'discharge_start_time' => 'required',
            'target_electric_price_cents' => 'required|numeric',
            'forced_charge' => 'required|boolean',
            'charge_start_time' => 'required',
            'battery_level_percent' => 'required|numeric|min:0|max:100',
        ]);

        $batterySetting->update($validated);

        return redirect()->route('battery-settings.index')
            ->with('success', 'Battery setting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BatterySetting $batterySetting)
    {
        $batterySetting->delete();

        return redirect()->route('battery-settings.index')
            ->with('success', 'Battery setting deleted successfully.');
    }
}
