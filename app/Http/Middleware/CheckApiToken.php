<?php

namespace App\Http\Middleware;

use App\Events\SecurityAuditEvent;
use App\Models\Event;
use App\Models\Monitor;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }
        $providedToken = $request->header('X-API-TOKEN')
            ?? $request->query('api_token')
            ?? $request->input('api_token')
            ?? $request->route('api_token');
        // 1. Event AV edit token check
        if ($request->route('event')) {
            $event = $request->route('event');
            if ($event instanceof Event && $providedToken && hash_equals((string) $event->api_token, (string) $providedToken)) {
                return $next($request);
            }
            throw new AuthorizationException('Invalid API token for the specified event.');
        }
        // 2. Monitor Token Check (Bound Monitor or generic monitor token)
        $routeMonitor = $request->route('monitor');
        if ($routeMonitor instanceof Monitor) {
            return $next($request);
        } elseif ($providedToken && Monitor::where('api_token', $providedToken)->exists()) {
            return $next($request);
        }
        event(new SecurityAuditEvent(
            action: 'auth.token_invalid',
            description: 'Unauthorized access attempt to token-protected route',
            userId: null,
            realmId: null,
            context: ['url' => $request->fullUrl(), 'ip' => $request->ip()]
        ));
        throw new AuthorizationException('You must provide a valid API key.');
    }
}
