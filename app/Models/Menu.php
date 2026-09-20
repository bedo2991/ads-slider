<?php

namespace App\Models;

use App\Observers\MenuObserver;
use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([MenuObserver::class])]
class Menu extends Model
{
    use HasFactory, RealmTrait;

    protected $guarded = ['id', 'user_id'];

    protected $hidden = ['name', 'created_at', 'updated_at', 'user_id', 'path', 'pivot', 'realm_id'];

    protected $with = ['monitors:monitors.id'];

    protected function getJsonContentAttribute()
    {
        if (! Storage::disk('public')->exists(config('ads.menu_basepath').$this->path)) {
            return null;
        }
        $content = Storage::disk('public')->get(config('ads.menu_basepath').$this->path);
        $res = json_decode($content, true);
        if (! is_null($res)) {
            if (isset($res[0]) && is_array($res[0])) {
                $res = $res[0];
            }
            if (is_array($res)) {
                $res['id'] = $this->id;

                return $res;
            }
        }

        return null;
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
    public function events()
    {
        return $this->belongsToMany(Event::class);
    }

    /**
     * @return BelongsToMany
     */
    public function templates()
    {
        return $this->belongsToMany(Template::class);
    }

    public function monitors()
    {
        return $this->belongsToMany(Monitor::class);
    }
}
