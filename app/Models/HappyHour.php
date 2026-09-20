<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HappyHour extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'event_id'];

    protected $hidden = ['created_at', 'updated_at', 'event_id'];

    /**
     * @return BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /** This function is necessary to correctly fill the "value" property of the form */
    protected function start(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (new Carbon($value))->format('Y-m-d\TH:i')
        );
    }

    /** This function is necessary to correctly fill the "value" property of the form */
    protected function end(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (new Carbon($value))->format('Y-m-d\TH:i')
        );
    }

    protected function realmId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->event->realm_id
        );
    }
}
