<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\PictureSource;
use App\Providers\ItemUpdated;
use App\Traits\UploadTrait;
use ColorThief\ColorThief;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PictureController extends Controller
{
    use UploadTrait;

    public function __construct()
    {
        $this->authorizeResource(Picture::class, 'pic');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Factory|\Illuminate\View\View
     */
    public function index(): View
    {
        $pictures = Picture::ofRealm(Auth::user()->realm_id)->orderBy('updated_at', 'DESC')->with(['user:id,name', 'slides:id,start_time,end_time,scheduleable_id,scheduleable_type'])->paginate(10);

        return view('pics.index', compact('pictures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $monitors = Monitor::ofRealm(Auth::user()->realm_id)->orderBy('name')->get();

        return view('pics.create', compact('monitors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws FileNotFoundException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|max:191',
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'duration' => 'required|integer|min:1|max:120',
        ]);
        $picture = new Picture;
        $picture->realm_id = Auth::user()->realm_id;
        $picture->user_id = Auth::id();
        $picture->name = $request->input('name');
        $picture->duration = $request->input('duration', 15);

        if ($request->has('upload')) {
            // Get image file
            $image = $request->file('upload');
            // Make a image name based on user name and current timestamp
            $name = Str::slug($request->input('name')).'_'.rand(0, 32000000).'.'.$image->getClientOriginalExtension();
            // Define folder path
            $folder = config('ads.pic_basepath');
            // Upload image
            $this->uploadOne($image, $folder, 'public', $name);

            // Get dimensions from the temp file or uploaded file
            $size = getimagesize($image->getPathname());

            // Get the main and the opposite color
            $p = ColorThief::getPalette(/* Storage::url(config('ads.pic_basepath').$name) */ $image->get(), 3);
            $picture->bg_color = sprintf('#%02x%02x%02x', $p[0][0], $p[0][1], $p[0][2]);
            $picture->color = sprintf('#%02x%02x%02x', $p[2][0], $p[2][1], $p[2][2]);

            $picture->save();

            $picture->sources()->create([
                'path' => $name,
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'clock_location' => 5,
            ]);
        } else {
            $picture->save();
        }

        if ($request->has('monitors')) {
            $validMonitorIds = Monitor::ofRealm(Auth::user()->realm_id)
                ->whereIn('id', (array) $request->input('monitors'))
                ->pluck('id');
            $picture->monitors()->attach($validMonitorIds);
        }
        flash()->success('Picture created');
        Log::channel('crud')->info('Picture created', [
            'picture' => $picture,
        ]);

        return redirect()->route('pics.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Picture $pic): BinaryFileResponse
    {
        if (Auth::check()) {
            $this->authorize('view', $pic);
        } else {
            // Enforce monitor token realm scoping
            $token = $request->header('X-API-TOKEN') ?? $request->query('api_token');
            $monitor = Monitor::where('api_token', $token)->first();

            if (! $monitor || $monitor->realm_id !== $pic->realm_id) {
                abort(403, 'Unauthorized media access across tenants.');
            }
        }

        $targetWidth = (int) $request->input('width');
        $targetHeight = (int) $request->input('height');

        $source = $pic->getBestSource($targetWidth, $targetHeight);
        $path = $source ? $source->path : '';

        return response()->file(Storage::disk('public')->path(config('ads.pic_basepath').$path));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Factory|\Illuminate\View\View
     */
    public function edit(Picture $pic)
    {
        $monitors = Monitor::ofRealm(auth()->user()->realm_id)->orderBy('name')->get();

        return view('pics.edit', ['picture' => $pic, 'monitors' => $monitors]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Picture $pic)
    {
        $validated = $request->validate([
            'name' => 'required|max:191',
            'duration' => 'required|integer|min:1|max:120',
            'color' => 'required|hex_color',
            'bg_color' => 'required|hex_color',
            'clock_location' => 'required|integer|min:0|max:9',
            'monitors' => 'nullable|array',
            'monitors.*' => 'integer|exists:monitors,id',
        ]);

        DB::transaction(function () use ($pic, $request, $validated) {
            $clockLocation = $validated['clock_location'];
            unset($validated['clock_location']);

            $pic->fill($validated);
            // Realm ID should not change upon update. $pic->realm_id = Auth::user()->realm_id;
            $pic->user_id = Auth::id();
            $pic->save();

            // Enforce tenant scoping when syncing related monitors
            if ($request->has('monitors')) {
                $validMonitorIds = Monitor::ofRealm(auth()->user()->realm_id)
                    ->whereIn('id', $request->input('monitors', []))
                    ->pluck('id');
                $pic->monitors()->sync($validMonitorIds);
            }

            $pic->sources()->update(['clock_location' => $clockLocation]);
        }, 2);

        if ($pic->slides->count() > 0) {
            foreach ($pic->slides as $slide) {
                event(new ItemUpdated('ps', (object) ['id' => $slide->id, 'realm_id' => $slide->realm_id]));
            }
        }

        flash()->success('Picture Updated');
        Log::channel('crud')->info('Picture updated', [
            'picture' => $pic,
        ]);

        return redirect()->route('pics.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Picture $pic): JsonResponse
    {
        DB::beginTransaction();
        // Delete all slides that reference this picture
        $pic->slides()->delete();
        // Detach from all monitors
        $pic->monitors()->detach();

        $filesToDelete = $pic->sources->pluck('path')->toArray();
        if (empty($filesToDelete)) {
            // If no sources, just delete the pic
        }

        // Delete the picture
        $pic->delete();

        // Delete the files
        foreach ($filesToDelete as $path) {
            Storage::disk('public')->delete(config('ads.pic_basepath').$path);
        }

        DB::commit();

        event(new SecurityAuditEvent(
            action: 'picture.deleted',
            description: "Picture '{$pic->name}' (ID: {$pic->id}) permanently deleted by user ID: ".Auth::id(),
            userId: Auth::id(),
            realmId: $pic->realm_id,
            context: ['picture_id' => $pic->id, 'picture_name' => $pic->name]
        ));

        // Everything went fine
        flash()->success('Picture deleted');
        Log::channel('crud')->warning('Picture deleted', [
            'picture' => $pic,
            'user' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }

    public static function getPicturesOptionArray()
    {
        return Picture::orderBy('name')->pluck('name', 'id')->all();
    }

    public function storeSource(Request $request, Picture $pic): RedirectResponse
    {
        $this->authorize('update', $pic);

        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->has('upload')) {
            $image = $request->file('upload');
            $name = Str::slug($pic->name).'_'.rand(0, 32000000).'.'.$image->getClientOriginalExtension();
            $folder = config('ads.pic_basepath');

            $this->uploadOne($image, $folder, 'public', $name);

            $size = getimagesize($image->getPathname());

            $pic->sources()->create([
                'path' => $name,
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'clock_location' => $pic->clock_location,
            ]);

            flash()->success('Source added');
        }

        return redirect()->route('pics.edit', $pic);
    }

    public function updateSource(Request $request, PictureSource $source): JsonResponse
    {
        $this->authorize('update', $source->picture);

        $validated = $request->validate([
            'clock_location' => 'required|integer|min:0|max:9',
        ]);

        $source->update($validated);

        event(new ItemUpdated('ps', (object) ['id' => $source->picture->id, 'realm_id' => $source->picture->realm_id]));

        return response()->json(['status' => 'success']);
    }

    public function destroySource(PictureSource $source): RedirectResponse
    {
        $this->authorize('update', $source->picture);
        if ($source->picture->sources()->count() <= 1) {
            flash()->error(__('Cannot delete the last source. Delete the entire picture configuration instead'));

            return redirect()->back();
        }
        $filePath = config('ads.pic_basepath').$source->path;
        $picture = $source->picture;
        DB::transaction(function () use ($source, $filePath) {
            $source->delete();
            Storage::disk('public')->delete($filePath);
        });
        event(new SecurityAuditEvent(
            action: 'picture.source_deleted',
            description: "Picture source (ID: {$source->id}, Path: {$source->path}) deleted for Picture ID: {$picture->id}",
            userId: Auth::id(),
            realmId: $picture->realm_id,
            context: ['picture_id' => $picture->id, 'source_id' => $source->id]
        ));
        event(new ItemUpdated('ps', (object) ['id' => $picture->id, 'realm_id' => $picture->realm_id]));
        flash()->success('Source deleted');

        return redirect()->back();
    }
}
