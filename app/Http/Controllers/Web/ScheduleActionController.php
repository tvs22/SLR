<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScheduleActionController extends Controller
{
    public function start()
    {
        Artisan::call('schedule:run');
        return redirect('/schedule')->with('status', 'Scheduler started.');
    }

    public function stop()
    {
        Artisan::call('schedule:finish');
        return redirect('/schedule')->with('status', 'Scheduler stopped.');
    }

    public function pause()
    {
        Artisan::call('schedule:finish');
        return redirect('/schedule')->with('status', 'Scheduler paused.');
    }
}
