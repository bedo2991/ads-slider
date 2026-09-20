<?php

namespace App\Models;

use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Picture extends Model
{
    use HasFactory, RealmTrait;

    protected $fillable = ['name', 'color', 'bg_color', 'duration'];

    protected $hidden = ['created_at', 'updated_at', 'user_id', 'pivot', 'realm_id'];

    protected $appends = ['public_path', 'clock_location'];
    // protected $with='monitors:monitors.id,monitors.name';

    public function getPathAttribute(): ?string
    {
        if ($this->monitorWidth && $this->monitorHeight) {
            return $this->getBestSource($this->monitorWidth, $this->monitorHeight)?->path;
        }

        return $this->sources->first()?->path;
    }

    protected $monitorWidth;

    protected $monitorHeight;

    public function setMonitorDimensions($w, $h)
    {
        $this->monitorWidth = $w;
        $this->monitorHeight = $h;
    }

    public function getClockLocationAttribute($value)
    {
        if ($this->monitorWidth && $this->monitorHeight) {
            return $this->getBestSource($this->monitorWidth, $this->monitorHeight)?->clock_location ?? 5;
        }

        if ($value !== null) {
            return $value;
        }

        return $this->sources->first()?->clock_location ?? 5;
    }

    public function getBestSource(int $width, int $height): ?PictureSource
    {
        if ($width <= 0 || $height <= 0) {
            return $this->sources->first();
        }

        $targetRatio = $width / $height;
        $bestSource = null;
        $minDiff = PHP_FLOAT_MAX;

        foreach ($this->sources as $source) {
            if (! $source->width || ! $source->height) {
                continue;
            }
            $ratio = $source->width / $source->height;
            $diff = abs($ratio - $targetRatio);

            if ($diff < $minDiff) {
                $minDiff = $diff;
                $bestSource = $source;
            }
        }

        return $bestSource ?? $this->sources->first();
    }

    public function getPublicPathAttribute(): string
    {
        return Storage::disk('public')->url(config('ads.pic_basepath').$this->path);
    }

    // Relationship
    public function sources(): HasMany
    {
        return $this->hasMany(PictureSource::class);
    }

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
        static::updated(function ($picture) {
            // Update realm_id of schedules if picture's realm_id changed
            if ($picture->isDirty('realm_id')) {
                $picture->slides()->update(['realm_id' => $picture->realm_id]);
            }
        });

        static::deleting(function ($picture) {
            $picture->sources()->each(function ($source) {
                $source->delete();
            });
        });

        static::deleted(function ($picture) {
            $picture->slides()->delete();
        });
    }
}
