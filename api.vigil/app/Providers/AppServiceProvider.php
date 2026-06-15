<?php

namespace App\Providers;

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Incident\Listeners\OpenIncidentOnMonitorWentDown;
use App\Modules\Incident\Listeners\ResolveIncidentOnMonitorRecovered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MonitorWentDown::class, OpenIncidentOnMonitorWentDown::class);
        Event::listen(MonitorRecovered::class, ResolveIncidentOnMonitorRecovered::class);
    }
}
