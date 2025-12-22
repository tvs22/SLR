<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;
use Cron\CronExpression;

class ScheduleController extends Controller
{
    public function __invoke(Request $request, Schedule $schedule)
    {
        $events = collect($schedule->events())->first();
        $cron = new CronExpression($events->expression);
        $nextRunDate = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        $isDue = $cron->isDue();

        return view('schedule.index', [
            'nextRunDate' => $nextRunDate,
            'isDue' => $isDue,
        ]);
    }
}
