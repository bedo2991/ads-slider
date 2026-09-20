<?php

namespace App\Http\Controllers;

use App\Console\Commands\ImportEvents;
use App\Events\SecurityAuditEvent;
use App\Models\EventsImport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EventsImportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $eventsImports = EventsImport::ofRealm(Auth::user()->realm_id)->paginate(10);

        return response(view('eventsImports.index', compact('eventsImports')));
    }

    public function runImport(EventsImport $import, Request $request): Response
    {
        // Authorize that the user owns/manages this import
        $this->authorize('update', $import);
        $force = $request->boolean('force', false) || filter_var($request->route('force'), FILTER_VALIDATE_BOOLEAN);
        $disabled = $request->boolean('disabled', false) || filter_var($request->route('disabled'), FILTER_VALIDATE_BOOLEAN);

        Log::channel('events_imports')->info("Manually running event import '{$import->import_name}' (ID: {$import->id}) by user ".auth()->id());

        event(new SecurityAuditEvent(
            action: 'events_import.executed',
            description: "Manual events import executed for '{$import->import_name}' (ID: {$import->id})",
            userId: auth()->id(),
            realmId: $import->realm_id,
            context: [
                'import_id' => $import->id,
                'import_name' => $import->import_name,
                'force' => $force,
                'disabled' => $disabled,
            ]
        ));

        $exitCode = Artisan::call('import:events', [
            'source' => [$import->id], '--force' => $force, '-D' => $disabled, '--isolated',
        ]);

        $logs = Artisan::output();
        $success = $exitCode === 0;

        $hasWarnings = str_contains($logs, ImportEvents::WARNING_TAG);

        if (! $success) {
            flash(__('Something went wrong during the import'))->error();
        } elseif ($success && ! $hasWarnings) {
            flash(__('Import run successfully'))->success();
        } else {
            flash(__('Import run, but some validation errors happened'))->warning();
        }

        return response(view('eventsImports.result', compact('logs')));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('eventsImports.create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventsImport $eventsImport): Response
    {
        $this->authorize('update', $eventsImport);

        return response(view('eventsImports.edit', compact('eventsImport')));
    }
}
