<?php

namespace App\Models;

use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Template extends Model
{
    use HasFactory, RealmTrait;

    protected $guarded = ['user_id', 'id', 'realm_id'];

    protected $hidden = ['created_at', 'updated_at', 'realm_id'];

    protected $casts = [
        'not_closing' => 'boolean',
        'final_round_confirmed' => 'boolean',
        'is_karaoke' => 'boolean',
    ];

    public function disableAllCheckBoxes()
    {
        $this->attributes['not_closing'] = false;
        $this->attributes['final_round_confirmed'] = false;
        $this->attributes['is_karaoke'] = false;
    }

    protected function getStartTimeAttribute($value)
    {
        return $this->schedule?->start_time ? substr($this->schedule->start_time, 0, 5) : null;
    }

    protected function getEndTimeAttribute($value)
    {
        return $this->schedule?->end_time ? substr($this->schedule->end_time, 0, 5) : null;
    }

    public function schedule()
    {
        return $this->morphOne(Schedule::class, 'scheduleable');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }
}
