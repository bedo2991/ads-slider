<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (\Illuminate\Http\Response|RedirectResponse)  $next
     * @return \Illuminate\Http\Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $role = 'realm_admin'): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if ($role === 'super_admin') {
            if (! $user->is_admin) {
                flash(__('Sorry, only super admins can do that'))->error();

                return redirect()->back();
            }
        } else {
            if (! ($user->is_admin || $user->is_realm_admin)) {
                flash(__('Sorry, only admins can do that'))->error();

                return redirect()->back();
            }
        }

        return $next($request);
    }
}
