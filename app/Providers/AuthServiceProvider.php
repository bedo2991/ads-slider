<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Event;
use App\Models\HappyHour;
use App\Models\Monitor;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\Template;
use App\Models\User;
use App\Policies\AlertPolicy;
use App\Policies\EventPolicy;
use App\Policies\EventsImportPolicy;
use App\Policies\HappyHourPolicy;
use App\Policies\MenuPolicy;
use App\Policies\MonitorPolicy;
use App\Policies\PicturePolicy;
use App\Policies\RealmPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\TemplatePolicy;
use App\Policies\UserPolicy;
use App\Policies\VideoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Alert::class => AlertPolicy::class,
        User::class => UserPolicy::class,
        HappyHour::class => HappyHourPolicy::class,
        Event::class => EventPolicy::class,
        Template::class => TemplatePolicy::class,
        Monitor::class => MonitorPolicy::class,
        Schedule::class => SchedulePolicy::class,
        Realm::class => RealmPolicy::class,
        Menu::class => MenuPolicy::class,
        Picture::class => PicturePolicy::class,
        Video::class => VideoPolicy::class,
        EventsImport::class => EventsImportPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Password::defaults(function () {
            return Password::min(10)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
        //
    }
}
