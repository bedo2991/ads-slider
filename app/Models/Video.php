<?php

namespace App\Models;

use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory, RealmTrait;

    protected $fillable = ['name', 'color', 'bg_color', 'clock_location'];

    protected $hidden = ['created_at', 'updated_at', 'user_id', 'pivot', 'realm_id'];
    // protected $with='monitors:monitors.id,monitors.name';

    /**
     * get the slides of the current user
     */
    public function slides()
    {
        return $this->morphMany(Schedule::class, 'scheduleable');
    }

    public function schedules()
    {
        return $this->morphMany(Schedule::class, 'scheduleable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }

    protected static function booted()
    {
        static::updated(function ($video) {
            // Update realm_id of schedules if video's realm_id changed
            if ($video->isDirty('realm_id')) {
                $video->slides()->update(['realm_id' => $video->realm_id]);
            }
        });

        static::deleted(function ($video) {
            Storage::disk('public')->delete(config('ads.vid_basepath').$video->path);
            $video->slides()->delete();
        });
    }
}
