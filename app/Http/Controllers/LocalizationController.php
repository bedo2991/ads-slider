<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LocalizationController extends Controller
{
    public function index(string $locale): RedirectResponse
    {
        App::setLocale($locale);
        Carbon::setLocale($locale);
        // store the locale in session so that the middleware can register it
        session()->put('locale', $locale);

        return redirect()->back();
    }
}
