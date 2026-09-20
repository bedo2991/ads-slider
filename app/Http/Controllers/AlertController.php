<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Alert;
use App\Providers\AlertCreated;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use stdClass;

class AlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Alert::class);

        return view('alerts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): RedirectResponse
    {
        $this->authorize('create', Alert::class);
        // Validate the request
        $validated = $request->validate([
            'title' => 'required|string|max:40',
            'message' => 'required|string|max:200',
            'link' => 'nullable|url',
            'showFor' => 'integer|min:1|max:900',
            'level' => 'integer|min:0|max:3',
        ]);

        $data = new stdClass;
        $data->title = $validated['title'];
        $data->message = $validated['message'];
        $data->url = $validated['link'];
        $data->timeoutInSeconds = $validated['showFor'] ?? 30;
        $data->realm_id = auth()->user()->realm_id;
        $data->level = intval($request->input('level', 0));
        $data->source = 'INTERN';
        event(new AlertCreated($data));

        event(new SecurityAuditEvent(
            action: 'alert.broadcast_created',
            description: "Broadcast alert '{$validated['title']}' dispatched by user ID: ".auth()->id(),
            userId: auth()->id(),
            realmId: auth()->user()->realm_id,
            context: [
                'title' => $validated['title'],
                'level' => $data->level,
                'timeout' => $data->timeoutInSeconds,
            ]
        ));
        flash(__('The alert has been sent'))->success();

        return back()->withInput();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Alert $alert)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alert $alert)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Alert $alert)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alert $alert)
    {
        //
    }
}
