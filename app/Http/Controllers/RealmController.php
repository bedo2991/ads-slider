<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Helpers\Debounce;
use App\Models\Realm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RealmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Realm $realm): View
    {
        Gate::authorize('update', $realm);

        return view('realms.edit', compact('realm'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function forceUpdateOrdersList(Realm $realm)
    {
        Log::channel('connections')->info(request()->ip().' forced updating orders list for realm: '.$realm->id);

        event(new SecurityAuditEvent(
            action: 'realm.orders_list_fetch_forced',
            description: "Orders list fetch triggered for Realm ID: {$realm->id}",
            userId: auth()->id(),
            realmId: $realm->id,
            context: [
                'realm_id' => $realm->id,
                'orders_link' => $realm->orders_link,
            ]
        ));

        Debounce::command(
            command: 'orderslist:fetch',
            delay: 5, // seconds
            parameters: ['--realm' => $realm->id, '--orders_link' => $realm->orders_link, '--important' => true],
            uniqueKey: 'forceUpdateOrdersList_'.$realm->id,
            toQueue: false, // optional, send command to the queue when executed
            outputBuffer: null, // optional, //see Artisan::call() signature
        );

        return response()->noContent();
    }
}
