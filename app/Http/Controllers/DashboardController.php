<?php

namespace App\Http\Controllers;

use App\BatterySetting;
use App\BatteryTransaction;
use App\SolarForecast;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $todayForecast = SolarForecast::whereDate('date', Carbon::today())->latest('hour')->first();
        $tomorrowForecast = SolarForecast::whereDate('date', Carbon::tomorrow())->latest('hour')->first();

        return view('dashboard', [
            'battery' => BatterySetting::latest()->first(),
            'prices' => Cache::get('latest_prices'),
            'transactions' => BatteryTransaction::latest()->limit(50)->get(),
            'last_updated' => Cache::get('last_updated'),
            'todayForecast' => $todayForecast ? $todayForecast->kwh : 0,
            'tomorrowForecast' => $tomorrowForecast ? $tomorrowForecast->kwh : 0,
        ]);
    }

    public function data()
    {
        $todayForecast = SolarForecast::whereDate('date', Carbon::today())->latest('hour')->first();
        $tomorrowForecast = SolarForecast::whereDate('date', Carbon::tomorrow())->latest('hour')->first();
        
        return response()->json([
            'battery' => BatterySetting::latest()->first(),
            'prices' => Cache::get('latest_prices'),
            'transactions' => BatteryTransaction::latest()->limit(50)->get(),
            'last_updated' => Cache::get('last_updated'),
            'todayForecast' => $todayForecast ? $todayForecast->kwh : 0,
            'tomorrowForecast' => $tomorrowForecast ? $tomorrowForecast->kwh : 0,
        ]);
    }
}
