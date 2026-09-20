<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Realm extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function removeOrdersPull(): bool
    {
        $this->orders_pull = null;

        return $this->save();
    }

    public function updateOrdersPull(): bool
    {
        $this->orders_pull = Str::ulid();

        return $this->save();
    }

    public static function getBroadcastChannelSecret(int $realmId): string
    {
        return substr(hash_hmac('sha256', 'realm_'.$realmId, config('app.key')), 0, 16);
    }

    public static function getBroadcastChannel(string $prefix, int $realmId): string
    {
        $secret = self::getBroadcastChannelSecret($realmId);

        return "{$prefix}-{$realmId}-{$secret}";
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function eventsImports(): HasMany
    {
        return $this->hasMany(EventsImport::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function pictures(): HasMany
    {
        return $this->hasMany(Picture::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
