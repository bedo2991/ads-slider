<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\Schedule;
use App\Models\Video;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class VideoSlideController extends Controller
{
    /**
     * @return mixed
     *
     * @deprecated
     */
    public static function getScheduledVideos()
    {
        return Schedule::where('scheduleable_type', 'VI')->active()->with('scheduleable.monitors:id');
    }

    public static function getScheduledVideosOnMonitor(Monitor $m): ?Builder
    {
        if ($m->show_videos === 0) {
            return null;
        }

        return Schedule::where('scheduleable_type', 'VI')->active()->whereHasMorph('scheduleable', [Video::class], function (Builder $query) use ($m) {
            $query->where('realm_id', $m->realm_id);
            // Check for monitors inside the morph constraint where we know it's a Video
            $query->where(function ($q) use ($m) {
                $q->whereDoesntHave('monitors')
                    ->orWhereHas('monitors', function ($mq) use ($m) {
                        $mq->where('monitors.id', $m->id);
                    });
            });
        })->with('scheduleable');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Factory|View
     */
    public function index()
    {
        $this->authorize('viewAny', Schedule::class);

        $videoSlides = Schedule::where('scheduleable_type', 'VI')->whereHasMorph('scheduleable', [Video::class], function (Builder $query) {
            $query->where('realm_id', auth()->user()->realm_id);
        })->orderBy('updated_at', 'DESC')->paginate(10);

        return view('vidSlides.index', compact('videoSlides'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     */
    public function create($video_id = null)
    {
        $this->authorize('create', Schedule::class);

        if ($video_id !== null) {
            Video::ofRealm(auth()->user()->realm_id)->findOrFail($video_id);
        }

        $allVideos = Video::ofRealm(auth()->user()->realm_id)->orderBy('name')->get(['id', 'name']);

        return view('vidSlides.create', ['allVideos' => $allVideos, 'selected_video' => $video_id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $vidSlide): View
    {
        $this->authorize('update', $vidSlide);

        $allVideos = Video::ofRealm(auth()->user()->realm_id)->orderBy('name')->get(['id', 'name']);

        return view('vidSlides.edit', compact('vidSlide', 'allVideos'));
    }
}
