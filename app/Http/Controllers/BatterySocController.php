<?php

namespace App\Http\Controllers;

use App\BatterySoc;
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
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $socData = BatterySoc::orderBy('hour')->get();
        return view('battery_soc.index', compact('socData'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('battery_soc.create');
    }

    /**
     * Store a newly created resource in storage.
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
     */
    public function show(BatterySoc $batterySoc)
    {
        return view('battery_soc.show', compact('batterySoc'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BatterySoc $batterySoc)
    {
        return view('battery_soc.edit', compact('batterySoc'));
    }

    /**
     * Update the specified resource in storage.
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
     */
    public function destroy(BatterySoc $batterySoc)
    {
        $batterySoc->delete();

        return redirect()->route('battery_soc.index')
                        ->with('success', 'Battery SOC deleted successfully');
    }
}
