<?php

namespace App\Providers;

use App\Events\SecurityAuditEvent;
use App\Listeners\LogSecurityAudit;
use App\Models\Event;
use App\Models\HappyHour;
use App\Models\Monitor;
use App\Models\Schedule;
use App\Observers\EventObserver;
use App\Observers\HappyHourObserver;
use App\Observers\MonitorObserver;
use App\Observers\ScheduleObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ItemDeleted::class => [],
        ItemUpdated::class => [],
        ItemCreated::class => [],
        WeatherDataUpdated::class => [],
        OrderslistUpdated::class => [],
        AlertCreated::class => [],
        SecurityAuditEvent::class => [
            LogSecurityAudit::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Event::observe(EventObserver::class);
        Monitor::observe(MonitorObserver::class);
        Schedule::observe(ScheduleObserver::class);
        HappyHour::observe(HappyHourObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
