<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Monitor;
use App\Models\Video;
use App\Providers\ItemUpdated;
use App\Traits\UploadTrait;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    use UploadTrait;

    public function __construct()
    {
        $this->authorizeResource(Video::class, 'video');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $videos = Video::ofRealm(Auth::user()->realm_id)->orderBy('updated_at', 'DESC')->with(['user:id,name', 'slides:id,start_time,end_time,scheduleable_id,scheduleable_type'])->paginate(10);

        return view('videos.index', compact('videos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $monitors = Monitor::ofRealm(Auth::user()->realm_id)->orderBy('name')->get();

        return view('videos.create', compact('monitors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws FileNotFoundException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'upload' => 'required|file|mimes:mp4|max:25000',
        ]);
        $video = new Video;
        $video->realm_id = Auth::user()->realm_id;
        $video->user_id = Auth::id();
        $video->name = $request->input('name');

        if ($request->hasFile('upload')) {
            // Get video file
            $vidFile = $request->file('upload');
            // Make a video name based on user name and current timestamp
            $name = Str::slug($request->input('name')).'_'.rand(0, 32000000).'.'.$vidFile->getClientOriginalExtension();
            // Define folder path
            $folder = config('ads.vid_basepath');
            // Upload video
            $this->uploadOne($vidFile, $folder, 'public', $name);
            // Set the video path in the model
            $video->path = $name;

            // Statically set the main and the opposite color
            $video->bg_color = '#000000';
            $video->color = '#FFFFFF';
        }
        $video->save();
        if ($request->has('monitors')) {
            // Enforce tenant scoping when attaching related monitors
            $validMonitorIds = Monitor::ofRealm(auth()->user()->realm_id)
                ->whereIn('id', $request->input('monitors', []))
                ->pluck('id');
            $video->monitors()->attach($validMonitorIds);
        }
        flash()->success('Video created');
        Log::channel('crud')->info('Video created', [
            'video' => $video,
        ]);

        return redirect()->route('videos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Video $video): BinaryFileResponse
    {
        if (Auth::check()) {
            $this->authorize('view', $video);
        } else {
            // Enforce monitor token realm scoping
            $token = $request->header('X-API-TOKEN') ?? $request->query('api_token');
            $monitor = Monitor::where('api_token', $token)->first();

            if (! $monitor || $monitor->realm_id !== $video->realm_id) {
                abort(403, 'Unauthorized media access across tenants.');
            }
        }

        return response()->file(Storage::disk('public')->path(config('ads.vid_basepath').$video->path));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Video $video): View
    {
        $monitors = Monitor::ofRealm(auth()->user()->realm_id)->orderBy('name')->get();

        return view('videos.edit', ['video' => $video, 'monitors' => $monitors]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Video $video): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|max:191',
            'color' => 'required|hex_color',
            'bg_color' => 'required|hex_color',
            'clock_location' => 'required|integer|min:0|max:9',
            'monitors' => 'nullable|array',
            'monitors.*' => 'integer|exists:monitors,id',
        ]);

        DB::transaction(function () use ($video, $request, $validated) {
            $video->fill($validated);
            // realm_id should not be changed upon updates. $video->realm_id = Auth::user()->realm_id;
            $video->user_id = Auth::id();
            $video->save();

            // Enforce tenant scoping when syncing related monitors
            if ($request->has('monitors')) {
                $validMonitorIds = Monitor::ofRealm(auth()->user()->realm_id)
                    ->whereIn('id', $request->input('monitors', []))
                    ->pluck('id');
                $video->monitors()->sync($validMonitorIds);
            }
        }, 2);

        flash()->success('Video Updated');

        if ($video->slides->count() > 0) {
            foreach ($video->slides as $slide) {
                event(new ItemUpdated('vs', (object) ['id' => $slide->id, 'realm_id' => $slide->realm_id]));
            }
        }

        Log::channel('crud')->info('Video updated', [
            'video' => $video,
        ]);

        return redirect()->route('videos.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Video $video): JsonResponse
    {
        // Detach from all monitors
        $video->monitors()->detach();

        // Delete the video
        // The deletion of the file and the slides is handled by the model events
        $video->delete();

        event(new SecurityAuditEvent(
            action: 'video.deleted',
            description: "Video '{$video->name}' (ID: {$video->id}) deleted by user ID: ".Auth::id(),
            userId: Auth::id(),
            realmId: $video->realm_id,
            context: ['video_id' => $video->id, 'video_name' => $video->name]
        ));

        flash()->success('Video deleted');
        Log::channel('crud')->warning('Video deleted', [
            'video' => $video,
            'user' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }

    public static function getVideosOptionArray()
    {
        return Video::orderBy('name')->pluck('name', 'id')->all();
    }
}
