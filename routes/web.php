<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventsImportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\PictureSlideController;
use App\Http\Controllers\RealmController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoSlideController;
use App\Livewire\EventsList;
use App\Livewire\PastEventsList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home');
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('lang/{locale}', [LocalizationController::class, 'index']);
Route::get('status.json', function () {
    return ['status' => 'OK'];
});
Route::get('requestpull/{realm:orders_pull}', [RealmController::class, 'forceUpdateOrdersList'])
    ->name('realm.requestpull')
    ->middleware('throttle:10,1');

// Public monitor & display endpoints
Route::get('/latest/{monitor:api_token}', [MonitorController::class, 'collectLastUpdate'])->name('latest');
Route::get('/showEvents/{monitor:api_token}', [MonitorController::class, 'display'])->name('showEventsToken')
    ->missing(function (Request $request) {
        abort(403);
    });
Route::get('Content/{monitor:api_token}/data.json', [MonitorController::class, 'getJson']);

// Authentication routes
Auth::routes(['register' => false, 'verify' => true]);

// Authenticated & verified users
Route::middleware(['verified', 'auth'])->group(function () {
    // User profile
    Route::resource('users', UserController::class)->only(['edit', 'update']);

    // Events
    Route::get('/events', EventsList::class)->name('events.index');
    Route::get('/events/expired', PastEventsList::class)->name('events.expired');
    Route::get('/events/create/{template_id}', [EventController::class, 'create'])->name('events.create.template');
    Route::resource('events', EventController::class)->only(['create', 'edit']);

    // Templates
    Route::resource('templates', TemplateController::class)->only(['index', 'create', 'edit']);

    // Pictures & Picture Slides
    Route::resource('pics', PictureController::class)->except(['show']);
    Route::post('pics/{pic}/source', [PictureController::class, 'storeSource'])->name('pics.storeSource');
    Route::put('pics/source/{source}', [PictureController::class, 'updateSource'])->name('pics.updateSource');
    Route::delete('pics/source/{source}', [PictureController::class, 'destroySource'])->name('pics.destroySource');
    Route::get('/picSlides/create/{picture_id}', [PictureSlideController::class, 'create']);
    Route::resource('picSlides', PictureSlideController::class)->only(['index', 'create', 'edit']);

    // Videos & Video Slides
    Route::resource('videos', VideoController::class)->except(['show']);
    Route::get('/vidSlides/create/{video_id}', [VideoSlideController::class, 'create']);
    Route::resource('vidSlides', VideoSlideController::class)->only(['index', 'create', 'edit']);

    // Menus
    Route::resource('menus', MenuController::class)->only(['index', 'create', 'edit', 'update', 'destroy']);

    // Monitors
    Route::resource('monitors', MonitorController::class)->except(['destroy', 'store', 'update']);
    Route::get('/show/{monitor}', [MonitorController::class, 'display'])
        ->name('showEvents')
        ->middleware('can:view,monitor');
});

// Token-protected routes
Route::middleware('token')->group(function () {
    // Event API edits
    Route::get('/events/{event}/edit/{api_token}', [EventController::class, 'edit'])->name('events.avedit');
    // Route::put('/events/{event}/{api_token}', [EventController::class, 'update'])->name('events.avupdate');

    // Media views
    Route::get('pics/{pic}', [PictureController::class, 'show'])->name('pics.show');
    Route::get('menus/{menu}', [MenuController::class, 'show'])->name('menus.show');
    Route::get('videos/{video}', [VideoController::class, 'show'])->name('videos.show');

    // Monitor statistics
    Route::post('monitors/stats/{monitor:api_token}', [MonitorController::class, 'storeStats']);
});

// Admins only
Route::middleware(['verified', 'auth', 'isAdmin'])->group(function () {
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts', [AlertController::class, 'create']);
    Route::resource('realms', RealmController::class)->only(['edit']);
    Route::resource('eventsImports', EventsImportController::class)->only(['index', 'create', 'edit']);
    Route::post('/eventsImports/run/{import}/{force?}/{disabled?}', [EventsImportController::class, 'runImport'])->name('eventsImports.run');
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});
