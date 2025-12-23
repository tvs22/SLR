<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BatteryControlService;

class BatteryCheck extends Command
{
    protected $signature = 'battery:check';
    protected $description = 'Fetch prices and update battery forced discharge';

    public function handle(BatteryControlService $service)
    {
        //sleep(41);
        $service->run();
    }
}