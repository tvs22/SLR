<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

// The /battery-soc/get-soc route is public and must be defined
// before the resource route to have priority.
Route::get('battery-soc/get-soc', 'App\Http\Controllers\BatterySocController@getSoc')->name('battery_soc.get-soc');
Route::get('solar-forecasts/get-forecasts', 'App\Http\Controllers\SolarForecastController@getSolarForecasts')->name('solar-forecasts.get-forecasts');

Route::get('/internal/cron/battery-check', function () {
    Artisan::call('battery:check');
    return response()->json(['ok' => true]);
});

Route::group(['middleware' => ['auth']], function () {
    // The root route now points to the dashboard.
    Route::get('/', 'App\Http\Controllers\DashboardController@index')->name('home');

    // Redirect the old /home route to the new root.
    Route::redirect('/home', '/', 301);

    Route::resource('battery-settings', 'App\Http\Controllers\BatterySettingsController');
    Route::resource('battery_soc', 'App\Http\Controllers\BatterySocController');
    Route::get('solar-forecasts/delete-all', 'App\Http\Controllers\SolarForecastController@deleteAll')->name('solar-forecasts.delete-all');
    Route::resource('solar-forecasts', 'App\Http\Controllers\SolarForecastController');
    Route::resource('pv-yields', 'App\Http\Controllers\PvYieldController');
    Route::get('/dashboard/data', 'App\Http\Controllers\DashboardController@data')->name('dashboard.data');
    Route::resource('battery-strategies', 'App\Http\Controllers\BatteryStrategyController');
    Route::get('/price/simulation', 'App\Http\Controllers\PriceController@simulation')->name('price.simulation');
});
