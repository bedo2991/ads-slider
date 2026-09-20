<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Picture;
use App\Models\Template;
use App\Models\Video;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Added by me, if in dev, register laravel/telescope
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        Livewire::setUpdateRoute(function ($handle) {
            $basePath = parse_url(config('app.url'), PHP_URL_PATH);
            $path = ($basePath ? rtrim($basePath, '/') : '').'/livewire/update';

            return Route::post($path, $handle)->middleware('web');
        });
        URL::forceRootUrl(config('app.url'));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'EV' => Event::class,
            'TE' => Template::class,
            'VI' => Video::class,
            'PI' => Picture::class,
        ]);

        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        // Only admins can access the log-viewer
        LogViewer::auth(function ($request) {
            return Auth::user()?->is_admin;
        });
    }
}
