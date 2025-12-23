<?php

namespace App\Http\Controllers;

use App\BatterySetting;
use App\BatteryTransaction;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        return view('dashboard', [
            'battery' => BatterySetting::latest()->first(),
            'prices' => Cache::get('latest_prices'),
            'transactions' => BatteryTransaction::latest()->limit(50)->get(),
            'last_updated' => Cache::get('last_updated'),
        ]);
    }

    public function data()
    {
        return response()->json([
            'battery' => BatterySetting::latest()->first(),
            'prices' => Cache::get('latest_prices'),
            'transactions' => BatteryTransaction::latest()->limit(50)->get(),
            'last_updated' => Cache::get('last_updated'),
        ]);
    }
}
