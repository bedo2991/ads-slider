<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Monitor;
use App\Models\Template;
use BadMethodCallException;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * @param  Request  $request
     * @return Factory|View
     */
    public function showEvents($api_token): View
    {
        $m = Monitor::where('api_token', $api_token)->first();
        if (is_null($m)) {
            Log::channel('connections')->warning('showEvents - Monitor not found', ['api_token' => $api_token]);
            throw new BadMethodCallException('You must provide a valid api_key to use this service.');
        }
        Log::channel('connections')->info('showEvents started', ['monitor' => $m->name, 'token' => $m->api_token]);

        return view('showevents', [
            // 'id' => $request->query('id'),
            'monitor' => $m,
        ]);
    }

    /**
     * @return mixed
     */
    public static function getNotEndedEvents()
    {
        return Event::with(['schedule'])->ofRealm(auth()->user()->realm_id)->whereHas('schedule', function (Builder $query) {
            $query->where('end', '>=', Carbon::today()->toDateString())->orWhereNull('end');
        })->with(['user']);
    }

    /**
     * Get all events who are currently scheduled
     *
     * @return mixed
     */
    public static function getScheduledEvents(int $realm_id)
    {
        return Event::with(['schedule'])
            ->ofRealm($realm_id)
            ->whereHas('schedule', function (Builder $query) {
                $query->whereDate('end', '>=', Carbon::today()->toDateString())->orWhereNull('end');
            })
            ->where(function (Builder $query) {
                // Determine if we should include an event based on cancellation and schedule status
                // We want to exclude events where (cancelled = true AND schedule.disabled = true)
                // This is equivalent to including items where NOT (cancelled = true AND schedule.disabled = true)
                // Which is (cancelled = false OR schedule.disabled = false)
                $query->where('cancelled', false)
                    ->orWhereHas('schedule', function (Builder $q) {
                        $q->where('disabled', false);
                    });
            });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($template_id = null): View
    {
        $allTemplates = null;
        if ($template_id !== null) {
            // User selected a template
            $template = Template::ofRealm(auth()->user()->realm_id)->findOrFail($template_id);

            return view('events.create', compact('template'));
        }

        // Return all templates to show them to the user
        $allTemplates = Template::ofRealm(auth()->user()->realm_id)->orderby('name')->get(['id', 'name']);

        return view('events.create', compact('allTemplates'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Request $request, Event $event): View
    {
        $avUpdating = $request->routeIs('events.avedit');

        if (! $avUpdating) {
            $this->authorize('update', $event);
        } else {
            $this->authorize('avUpdate', $event);
        }

        return view('events.edit', compact('event', 'avUpdating'));
    }
}
