<?php

namespace App\Models;

use App\Traits\RealmTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, RealmTrait;

    protected $guarded = ['user_id', 'id', 'api_token', 'realm_id'];

    protected $appends = ['real_start_date', 'real_end_date', 'start_time', 'end_time', 'start_date', 'end_date', 'repeat'];

    protected $hidden = ['user_id', 'created_at', 'updated_at', 'api_token', 'import_id', 'events_import_id'];

    protected $casts = [
        'not_closing' => 'boolean',
        'final_round_confirmed' => 'boolean',
        'is_karaoke' => 'boolean',
        'is_protected' => 'boolean',
        'cancelled' => 'boolean',
    ];

    /**
     * @deprecated This method is probably not used and shall be removed
     */
    public function disableAllCheckBoxes(): void
    {
        $this->attributes['not_closing'] = false;
        $this->attributes['final_round_confirmed'] = false;
        $this->attributes['is_karaoke'] = false;
        $this->attributes['is_protected'] = false;
        $this->attributes['cancelled'] = false;
    }

    /**
     * @return false|string|null
     */
    protected function getStartTimeAttribute(): ?string
    {
        return $this->schedule?->start_time ? substr($this->schedule->start_time, 0, 5) : null;
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->schedule && $this->schedule->end) {
            $endDate = Carbon::parse($this->schedule->end->toDateString().' '.$this->schedule->end_time);

            return $endDate->addMinutes(30)->lte(Carbon::now());
        }

        return false;
    }

    public function schedule()
    {
        return $this->morphOne(Schedule::class, 'scheduleable');
    }

    /**
     * @return false|string|null
     */
    protected function getEndTimeAttribute(): ?string
    {
        return $this->schedule?->end_time ? substr($this->schedule->end_time, 0, 5) : null;
    }

    /**
     * Get the event start date
     */
    public function getStartDateAttribute(): ?string
    {
        return $this->schedule?->start?->toDateString();
    }

    /**
     * Get the event end date
     */
    public function getEndDateAttribute(): ?string
    {
        return $this->schedule?->end?->toDateString();
    }

    /**
     * Get the event repeat string
     */
    public function getRepeatAttribute(): ?string
    {
        return $this->schedule?->repeat;
    }

    public function removeApiToken(): bool
    {
        $this->api_token = null;

        return $this->save();
    }

    public function updateApiToken(): bool
    {
        $this->api_token = Str::random(80);

        return $this->save();
    }

    /**
     * Compute the day in format Y-m-d H:i:s of the next occurrence of this event
     *
     * @return mixed|string
     */
    public function getRealStartDateAttribute(): ?string
    {
        return $this->schedule?->real_start_date;
    }

    /**
     * Get the event end date as Y-m-d H:i:s
     *
     * @return Carbon
     */
    public function getRealEndDateAttribute(): ?string
    {
        return $this->schedule?->real_end_date;
    }

    /**
     * I don't think this function is actually used/works
     *
     * @deprecated
     *
     * @return mixed
     */
    public function scopeCurrentUser($query)
    {
        // TODO: check if admin, then all
        if (Auth::user()->is_admin) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    /**
     * icon should always be lower case
     */
    protected function icon(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => is_null($value) ? null : strtolower($value),
        );
    }

    /**
     * get the user that last touched the event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * get the events import that generated the event
     */
    public function events_import(): BelongsTo
    {
        return $this->belongsTo(EventsImport::class);
    }

    /**
     * get the menus owned by the event
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class);
    }

    /**
     * get the happy hour owned by the event
     */
    public function happy_hour(): HasOne
    {
        return $this->hasOne(HappyHour::class);
    }

    protected static function booted()
    {
        static::updated(function ($event) {
            // Update realm_id of schedules if event's realm_id changed
            if ($event->isDirty('realm_id')) {
                $event->schedule()->update(['realm_id' => $event->realm_id]);
            }
        });

        static::deleted(function ($event) {
            $event->schedule()->delete();
            $event->happy_hour()->delete();
        });
    }

    /**
     * Change from minute to ms
     *
     * @return Illuminate\Database\Eloquent\Casts\Attribute
     */
    // protected function preparationTime(): Attribute
    // {
    //  return Attribute::make(
    //    get: fn ($value) =>  $value ? $value * 60000 : null,
    //  );
    // }
}
