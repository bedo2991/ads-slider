<?php

namespace App\Models;

use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Monitor extends Authenticatable
{
    use HasFactory, RealmTrait, SoftDeletes;

    protected $guarded = ['id', 'api_token', 'user_id', 'last_restarted_at', 'last_ping', 'stats', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['api_token', 'created_at', 'updated_at', 'deleted_at', 'user_id', 'pivot', 'stats', 'last_restarted_at', 'last_ping'];

    protected $casts = [
        'show_final_rounds' => 'boolean',
        'show_we_are_closing' => 'boolean',
        'show_we_are_closed_marketing' => 'boolean',
        'show_cancelled_events' => 'boolean',
        'show_menus' => 'boolean',
        'show_happy_hours' => 'boolean',
        'show_pictures' => 'boolean',
        'show_videos' => 'boolean',
        'show_karaoke' => 'boolean',
        'show_weather_forecast' => 'boolean',
        'use_animations' => 'boolean',
        'show_marquee' => 'boolean',
        'show_event_while_is_happening' => 'boolean',
        'show_orderslist' => 'boolean',
        'show_preparation_countdowns' => 'boolean',
        'last_restarted_at' => 'datetime',
        'last_ping' => 'datetime',
        'stats' => AsArrayObject::class,
    ];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }

    public function pictures()
    {
        return $this->belongsToMany(Picture::class);
    }
}
