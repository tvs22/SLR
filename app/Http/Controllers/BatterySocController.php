<?php

namespace App\Http\Controllers;

use App\BatterySoc;
use App\Services\FoxEssService;
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

        return view('battery_soc.index', compact('socData', 'chartData'));
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

    /**
     * Get the current battery SOC from the FOX ESS API.
     */
    public function getSoc(FoxEssService $foxEssService)
    {
        // Delete old 'current' SOC data
        BatterySoc::where('type', 'current')
            ->whereDate('created_at', '<', Carbon::today())
            ->delete();

        $now = Carbon::now();
        $currentHour = $now->hour;

        // Check if a 'current' SOC value for this hour has already been recorded today.
        $existingEntry = BatterySoc::where('type', 'current')
            ->where('hour', $currentHour)
            ->whereDate('created_at', $now->today())
            ->first();

        if ($existingEntry) {
            return redirect()->route('battery_soc.index')
                            ->with('error', 'A "current" SOC value for this hour has already been recorded today.');
        }

        $soc = $foxEssService->getSoc();

        if ($soc !== null) {
            BatterySoc::create([
                'type' => 'current',
                'hour' => $currentHour,
                'soc' => $soc,
            ]);

            return redirect()->route('battery_soc.index')
                ->with('success', 'Battery SOC updated successfully.');
        }

        return redirect()->route('battery_soc.index')
            ->with('error', 'Failed to update Battery SOC.');
    }
}
