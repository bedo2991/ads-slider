<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Schedule;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Nette\NotImplementedException;

class PictureSlideController extends Controller
{
    /**
     * @return mixed
     *
     * @deprecated
     */
    public static function getScheduledPictures()
    {
        return Schedule::where('scheduleable_type', 'PI')->active()->with('scheduleable.monitors:id');
    }

    private function getBestPictureFormat($screenWidth, $screenHeight, array $availableFormats = ['16:9', '4:3'])
    {
        if ($screenWidth <= 0 || $screenHeight <= 0) {
            return $availableFormats[0] ?? '16:9';
        }

        // 1. Calculate the decimal ratio of the monitor
        $screenRatio = $screenWidth / $screenHeight;

        // 2. Define the decimal values for all possible formats
        $formatValues = [
            '16:9' => 16 / 9,   // 1.77
            '16:10' => 16 / 10,  // 1.6
            '4:3' => 4 / 3,    // 1.33
            '21:9' => 21 / 9,   // 2.33
            '9:16' => 9 / 16,   // 0.56
            '1:1' => 1 / 1,    // 1.0
        ];

        $bestFormat = null;
        $minDifference = PHP_FLOAT_MAX;

        // 3. Loop only through the formats you actually have available
        foreach ($availableFormats as $format) {
            if (! isset($formatValues[$format])) {
                continue;
            }

            $diff = abs($screenRatio - $formatValues[$format]);

            if ($diff < $minDifference) {
                $minDifference = $diff;
                $bestFormat = $format;
            }
        }

        return $bestFormat ?? $availableFormats[0];
    }

    public static function getScheduledPicturesOnMonitor(Monitor $m): ?Collection
    {
        if ($m->show_pictures === 0) {
            return null;
        }

        $query = Schedule::where('scheduleable_type', 'PI')->active()->whereHasMorph('scheduleable', [Picture::class], function (Builder $query) use ($m) {
            $query->where('realm_id', $m->realm_id);
            // Check for monitors inside the morph constraint where we know it's a Picture
            $query->where(function ($q) use ($m) {
                $q->whereDoesntHave('monitors')
                    ->orWhereHas('monitors', function ($mq) use ($m) {
                        $mq->where('monitors.id', $m->id);
                    });
            });
        })->with(['scheduleable' => function ($q) {
            $q->select('id', 'bg_color', 'color', 'realm_id', 'duration')->with('sources');
        }]);

        $results = $query->get();

        if (isset($m->stats['screen']['availWidth']) && isset($m->stats['screen']['availHeight'])) {
            $w = intval($m->stats['screen']['availWidth']);
            $h = intval($m->stats['screen']['availHeight']);
            foreach ($results as $slide) {
                if ($slide->scheduleable instanceof Picture) {
                    $slide->scheduleable->setMonitorDimensions($w, $h);
                    $slide->scheduleable->makeHidden('sources');
                }
            }
        }

        return $results;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Schedule::class);

        $pictureSlides = Schedule::where('scheduleable_type', 'PI')->orderBy('updated_at', 'DESC')->whereHasMorph('scheduleable', [Picture::class], function (Builder $query) {
            $query->where('realm_id', auth()->user()->realm_id);
        })
            ->with(['user:id,name', 'scheduleable:id,bg_color,color,realm_id'])
            ->paginate(10);

        return view('picSlides.index', compact('pictureSlides'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     */
    public function create($picture_id = null): View
    {
        $this->authorize('create', Schedule::class);
        if ($picture_id !== null) {
            Picture::ofRealm(auth()->user()->realm_id)->findOrFail($picture_id);
        }

        $allPictures = Picture::ofRealm(auth()->user()->realm_id)->orderBy('name')->get(['id', 'name']);

        return view('picSlides.create', [
            'allPictures' => $allPictures,
            'selected_picture' => $picture_id,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Schedule $picSlide)
    {
        throw new NotImplementedException;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $picSlide): View
    {
        $this->authorize('update', $picSlide);
        $allPictures = Picture::ofRealm(auth()->user()->realm_id)->orderBy('name')->get(['id', 'name']);

        return view('picSlides.edit', compact('allPictures', 'picSlide'));
    }
}
