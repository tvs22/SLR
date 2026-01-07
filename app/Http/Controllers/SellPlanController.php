<?php

namespace App\Http\Controllers;

use App\Models\SellPlan;
use Illuminate\Http\Request;

class SellPlanController extends Controller
{
    public function index()
    {
        $sellPlans = SellPlan::all();
        return view('sell-plans.index', compact('sellPlans'));
    }
}
