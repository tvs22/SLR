<?php

namespace App\Http\Controllers;

use App\Models\BatteryStrategy;
use Illuminate\Http\Request;

class BatteryStrategyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $batteryStrategies = BatteryStrategy::all();
        return view('battery-strategies.index', compact('batteryStrategies'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('battery-strategies.create');
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
            'name' => 'required',
            'sell_start_time' => 'required',
            'sell_end_time' => 'required',
            'buy_start_time' => 'required',
            'buy_end_time' => 'required',
            'soc_lower_bound' => 'required|numeric|min:0|max:100',
            'soc_upper_bound' => 'required|numeric|min:0|max:100',
        ]);

        BatteryStrategy::create($request->all());

        return redirect()->route('battery-strategies.index')
            ->with('success', 'Battery strategy created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return \Illuminate\Http\Response
     */
    public function show(BatteryStrategy $batteryStrategy)
    {
        return view('battery-strategies.show', compact('batteryStrategy'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return \Illuminate\Http\Response
     */
    public function edit(BatteryStrategy $batteryStrategy)
    {
        return view('battery-strategies.edit', compact('batteryStrategy'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BatteryStrategy $batteryStrategy)
    {
        $request->validate([
            'name' => 'required',
            'sell_start_time' => 'required',
            'sell_end_time' => 'required',
            'buy_start_time' => 'required',
            'buy_end_time' => 'required',
            'soc_lower_bound' => 'required|numeric|min:0|max:100',
            'soc_upper_bound' => 'required|numeric|min:0|max:100',
        ]);

        $batteryStrategy->update($request->all());

        return redirect()->route('battery-strategies.index')
            ->with('success', 'Battery strategy updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BatteryStrategy  $batteryStrategy
     * @return \Illuminate\Http\Response
     */
    public function destroy(BatteryStrategy $batteryStrategy)
    {
        $batteryStrategy->delete();

        return redirect()->route('battery-strategies.index')
            ->with('success', 'Battery strategy deleted successfully');
    }
}
