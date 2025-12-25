<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BatteryControlService;
use Illuminate\Support\Facades\Cache;

class BatteryCheck extends Command
{
    protected $signature = 'battery:check';
    protected $description = 'Fetch prices and update battery forced discharge and charge';

    public function handle(BatteryControlService $service)
    {
        $service->run();
        Cache::put('last_updated', now());
    }
}
