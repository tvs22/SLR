<?php

namespace App\Http\Controllers;

use App\Models\SellPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\SellPlansExport;
use Maatwebsite\Excel\Facades\Excel;

class SellPlanController extends Controller
{
    public function index()
    {
        $sellPlans = SellPlan::all()->groupBy(function($plan) {
            return Carbon::parse($plan->created_at)->toDateTimeString();
        });
        return view('sell-plans.index', compact('sellPlans'));
    }

    public function destroy(Request $request)
    {
        $selectedGroups = $request->input('selected_groups', []);
        if (!empty($selectedGroups)) {
            SellPlan::whereIn('created_at', $selectedGroups)->delete();
        }
        return redirect()->route('sell-plans.index');
    }

    public function export()
    {
        return Excel::download(new SellPlansExport, 'sell-plans.xlsx');
    }
}
