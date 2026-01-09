<?php

namespace App\Http\Controllers;

use App\BatterySetting;
use App\BatterySoc;
use App\BatteryTransaction;
use App\Models\BatteryStrategy;
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
        return view('dashboard', [
            'battery' => BatterySetting::latest()->first(),
            'prices' => Cache::get('latest_prices'),
            'last_updated' => Cache::get('last_updated'),
            'todayForecast' => SolarForecast::whereDate('date', Carbon::today())->latest('hour')->first()->kwh ?? 0,
            'tomorrowForecast' => SolarForecast::whereDate('date', Carbon::tomorrow())->latest('hour')->first()->kwh ?? 0,
            'soc' => Cache::get('soc'),
            'remaining_solar_generation_today' => Cache::get('remaining_solar_generation_today'),
            'forecast_soc' => Cache::get('forecast_soc'),
            'everyday_buy_plan' => Cache::get('everyday_buy_plan'),
            'essential_buy_plan' => Cache::get('essential_buy_plan'),
            'target_buy_plan' => Cache::get('target_buy_plan'),
            'evening_sell_strategy' => Cache::get('evening_sell_strategy'),
            'late_evening_sell_strategy' => Cache::get('late_evening_sell_strategy'),
            'late_night_sell_strategy' => Cache::get('late_night_sell_strategy'),
            'kwh_to_buy_essential' => Cache::get('kwh_to_buy_essential'),
            'kwh_to_buy_target' => Cache::get('kwh_to_buy_target'),
            'batteryTransactions' => BatteryTransaction::latest()->take(10)->get(),
            'batteryStrategies' => BatteryStrategy::where('is_active', true)->whereNotNull('sell_start_time')->get(),
            'solar_prices' => Cache::get('solar_prices'),
            'forecast_errors' => Cache::get('forecast_errors'),
            'offer_price' => Cache::get('offer_price'),
            'sell_score' => Cache::get('sell_score'),
            'threshold' => Cache::get('threshold'),
        ]);
    }

    public function data()
    {
        return response()->json([
            'battery' => BatterySetting::latest()->first(),
            'prices' => Cache::get('latest_prices'),
            'last_updated' => Cache::get('last_updated'),
            'todayForecast' => SolarForecast::whereDate('date', Carbon::today())->latest('hour')->first()->kwh ?? 0,
            'tomorrowForecast' => SolarForecast::whereDate('date', Carbon::tomorrow())->latest('hour')->first()->kwh ?? 0,
            'soc' => Cache::get('soc'),
            'remaining_solar_generation_today' => Cache::get('remaining_solar_generation_today'),
            'forecast_soc' => Cache::get('forecast_soc'),
            'everyday_buy_plan' => Cache::get('everyday_buy_plan'),
            'essential_buy_plan' => Cache::get('essential_buy_plan'),
            'target_buy_plan' => Cache::get('target_buy_plan'),
            'evening_sell_strategy' => Cache::get('evening_sell_strategy'),
            'late_evening_sell_strategy' => Cache::get('late_evening_sell_strategy'),
            'late_night_sell_strategy' => Cache::get('late_night_sell_strategy'),
            'batteryTransactions' => BatteryTransaction::latest()->take(10)->get(),
            'kwh_to_buy_essential' => Cache::get('kwh_to_buy_essential'),
            'kwh_to_buy_target' => Cache::get('kwh_to_buy_target'),
            'solar_prices' => Cache::get('solar_prices'),
            'forecast_errors' => Cache::get('forecast_errors'),
            'offer_price' => Cache::get('offer_price'),
            'sell_score' => Cache::get('sell_score'),
            'threshold' => Cache::get('threshold'),
        ]);
    }
}
