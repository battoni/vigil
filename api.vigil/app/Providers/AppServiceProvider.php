<?php

namespace App\Providers;

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Incident\Listeners\OpenIncidentOnMonitorWentDown;
use App\Modules\Incident\Listeners\ResolveIncidentOnMonitorRecovered;
use App\Modules\Notification\Listeners\SendAlertOnMonitorRecovered;
use App\Modules\Notification\Listeners\SendAlertOnMonitorWentDown;
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
        // Incident listeners first so the incident exists/resolves before the
        // alert listener looks it up.
        Event::listen(MonitorWentDown::class, OpenIncidentOnMonitorWentDown::class);
        Event::listen(MonitorWentDown::class, SendAlertOnMonitorWentDown::class);

        Event::listen(MonitorRecovered::class, ResolveIncidentOnMonitorRecovered::class);
        Event::listen(MonitorRecovered::class, SendAlertOnMonitorRecovered::class);
    }
}
