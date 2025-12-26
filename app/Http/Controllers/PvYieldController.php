<?php

namespace App\Http\Controllers;

use App\PvYield;
use Illuminate\Http\Request;

class PvYieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pvYields = PvYield::all();
        return view('pv_yields.index', compact('pvYields'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pv_yields.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'hour' => 'required|integer|min:0|max:23',
            'kwh' => 'required|numeric',
        ]);

        PvYield::create($validatedData);

        return redirect()->route('pv-yields.index')
                        ->with('success', 'PV Yield created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PvYield $pvYield)
    {
        return view('pv_yields.show', compact('pvYield'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PvYield $pvYield)
    {
        return view('pv_yields.edit', compact('pvYield'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PvYield $pvYield)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'hour' => 'required|integer|min:0|max:23',
            'kwh' => 'required|numeric',
        ]);

        $pvYield->update($validatedData);

        return redirect()->route('pv-yields.index')
                        ->with('success', 'PV Yield updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PvYield $pvYield)
    {
        $pvYield->delete();

        return redirect()->route('pv-yields.index')
                        ->with('success', 'PV Yield deleted successfully');
    }
}
