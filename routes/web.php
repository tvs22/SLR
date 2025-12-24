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

// The root route now points to the dashboard.
Route::get('/', 'App\Http\Controllers\DashboardController@index')->name('home');

// Redirect the old /home route to the new root.
Route::redirect('/home', '/', 301);

Route::resource('battery-settings', 'App\Http\Controllers\BatterySettingsController');
Route::resource('battery_soc', 'App\Http\Controllers\BatterySocController');
Route::get('/dashboard/data', 'App\Http\Controllers\DashboardController@data')->name('dashboard.data');

Route::get('/internal/cron/battery-check', function () {
    Artisan::call('battery:check');
    return response()->json(['ok' => true]);
});