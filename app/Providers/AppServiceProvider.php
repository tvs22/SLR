<?php

namespace App\Providers;

use App\BatterySetting;
use App\Models\BatteryStrategy;
use App\Observers\BatterySettingObserver;
use App\Observers\BatteryStrategyObserver;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @param UrlGenerator $url
     * @return void
     */
    public function boot(UrlGenerator $url)
    {
        if (env('APP_ENV') == 'production') {
            $url->forceScheme('https');
        }

        BatterySetting::observe(BatterySettingObserver::class);
        BatteryStrategy::observe(BatteryStrategyObserver::class);
    }
}
