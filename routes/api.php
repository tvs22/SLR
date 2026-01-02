<?php

use Illuminate\Http\Request;
use App\Http\Controllers\PriceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/price/predicted-prices', [PriceController::class, 'getPredictedPrices']);
Route::get('/price/simulate', [PriceController::class, 'simulation']);
Route::get('/price/pv-yield-backfill', [PriceController::class, 'pvYieldBackfill']);
