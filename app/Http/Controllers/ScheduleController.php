<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScheduleController extends Controller
{
    public function start()
    {
        Artisan::call('schedule:run');
        return response()->json(['message' => 'Scheduler started.']);
    }

    public function stop()
    {
        Artisan::call('schedule:finish');
        return response()->json(['message' => 'Scheduler stopped.']);
    }

    public function pause()
    {
        Artisan::call('schedule:finish');
        return response()->json(['message' => 'Scheduler paused.']);
    }
}
