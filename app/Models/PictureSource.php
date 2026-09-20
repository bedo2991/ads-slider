<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PictureSource extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::deleting(function (PictureSource $source) {
            if (Storage::disk('public')->exists(config('ads.pic_basepath').$source->path)) {
                Storage::disk('public')->delete(config('ads.pic_basepath').$source->path);
            }
        });
    }

    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    public function getRatioAttribute(): string
    {
        if (! $this->width || ! $this->height) {
            return 'N/A';
        }

        $commonRatios = [
            '16:9' => 16 / 9,
            '16:10' => 16 / 10,
            '4:3' => 4 / 3,
            '1:1' => 1,
            '9:16' => 9 / 16,
            '21:9' => 21 / 9,
        ];

        $currentRatio = $this->width / $this->height;

        foreach ($commonRatios as $label => $value) {
            if (abs($currentRatio - $value) < 0.02) {
                return $label;
            }
        }

        // GCD fallback
        $a = $this->width;
        $b = $this->height;
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return ($this->width / $a).':'.($this->height / $a);
    }
}
