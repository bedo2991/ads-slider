<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Menu;
use App\Models\Monitor;
use App\Traits\UploadTrait;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    use UploadTrait;

    public function __construct()
    {
        $this->authorizeResource(Menu::class, 'menu');
    }

    /**
     * @deprecated
     */
    public static function getMenusOptionArray()
    {
        return Menu::orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $menus = Menu::ofRealm(Auth::user()->realm_id)->orderBy('name')->with('user')->paginate(10);

        return view('menus.index', compact('menus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('menus.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'upload' => 'required',
        ]);
        $menu = new Menu;
        $menu->realm_id = Auth::user()->realm_id;
        $menu->user_id = Auth::id();
        $menu->name = $request->input('name');

        if ($request->has('upload')) {
            // Get image file
            $json = $request->file('upload');
            // Make a image name based on user name and current timestamp
            $name = Str::slug($request->input('name')).'_'.rand(0, 32000000).'.'.$json->getClientOriginalExtension();
            // Define folder path
            $folder = config('ads.menu_basepath');
            // Upload image
            $this->uploadOne($json, $folder, 'public', $name);
            // Set the picture path in the model
            $menu->path = $name;
        }
        $menu->save();
        if ($request->has('monitors')) {
            $validMonitorIds = Monitor::ofRealm(Auth::user()->realm_id)
                ->whereIn('id', (array) $request->input('monitors'))
                ->pluck('id');
            $menu->monitors()->attach($validMonitorIds);
        }
        Log::channel('crud')->info('Menu created', [
            'menu' => $menu,
            'user' => Auth::id(),
        ]);
        flash()->success('Menu created');

        return redirect()->route('menus.index');
    }

    /**
     * Display the specified resource.
     *
     * @return RedirectResponse|Redirector
     */
    public function show(Request $request, Menu $menu)
    {
        if (Auth::check()) {
            $this->authorize('view', $menu);
        } else {
            // Enforce monitor token realm scoping
            $token = $request->header('X-API-TOKEN') ?? $request->query('api_token');
            $monitor = Monitor::where('api_token', $token)->first();

            if (! $monitor || $monitor->realm_id !== $menu->realm_id) {
                abort(403, 'Unauthorized media access across tenants.');
            }
        }

        return response()->file(Storage::disk('public')->path(config('ads.menu_basepath').$menu->path), ['Content-Type' => 'application/json']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu): View
    {
        $monitors = Monitor::ofRealm(Auth::user()->realm_id)->orderBy('name')->get();

        return view('menus.edit', compact('menu', 'monitors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|max:191',
            'menu_content' => 'required|json',
            'monitors' => 'nullable|array',
            'monitors.*' => 'integer|exists:monitors,id',
        ]);

        DB::beginTransaction();
        try {
            $menuContent = $validated['menu_content'];
            unset($validated['menu_content'], $validated['monitors']);
            $menu->fill($validated);
            // realm_id should not be changed upon updates. $menu->realm_id = Auth::user()->realm_id;
            $menu->user_id = Auth::id();
            Storage::disk('public')->put(config('ads.menu_basepath').$menu->path, $menuContent);
            $menu->updateTimestamps(); // this forcing firing the updated event, otherwise file content changes are ignored
            $menu->save();

            if ($request->has('monitors')) {
                $validMonitorIds = Monitor::ofRealm(auth()->user()->realm_id)
                    ->whereIn('id', $request->input('monitors', []))
                    ->pluck('id');
                $menu->monitors()->sync($validMonitorIds);
            }
            DB::commit();
            flash()->success('Menu Updated');
            Log::channel('crud')->info('Menu updated', [
                'menu' => $menu,
                'user' => Auth::id(),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            flash()->error('Menu could not be updated');
            Log::error($e);
        }

        return redirect()->route('menus.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function destroy(Menu $menu): JsonResponse
    {
        DB::beginTransaction();
        // Detach from all menus
        $menu->events()->detach();
        // Detach from all monitors
        $menu->monitors()->detach();
        // Detach from all templates
        $menu->templates()->detach();
        // Delete the menu
        $menu->delete();
        // Delete the file
        if (! Storage::disk('public')->delete(config('ads.menu_basepath').$menu->path)) {
            DB::rollBack();
            throw new Exception('Could not delete the menu');
        }
        DB::commit();

        event(new SecurityAuditEvent(
            action: 'menu.deleted',
            description: "Menu '{$menu->name}' (ID: {$menu->id}) deleted by user ID: ".Auth::id(),
            userId: Auth::id(),
            realmId: $menu->realm_id,
            context: ['menu_id' => $menu->id, 'menu_name' => $menu->name]
        ));

        flash()->success('Menu deleted');
        Log::channel('crud')->warning('Menu deleted', [
            'menu' => $menu,
            'user' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }
}
