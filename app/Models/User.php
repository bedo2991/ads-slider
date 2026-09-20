<?php

namespace App\Models;

use App\Traits\RealmTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, RealmTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'realm_id', 'user_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'user_type', 'realm_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * get the events owned by the user
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * get the templates owned by the user
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /*
    * get the pictures owned by the user
     */
    public function pictures(): HasMany
    {
        return $this->hasMany(Picture::class);
    }

    /*
    * get the picture slides owned by the user
     */
    public function pictureSlides(): HasMany
    {
        return $this->hasMany(PictureSlide::class);
    }

    /*
    * get the events imports owned by the user
     */
    public function eventsImports(): HasMany
    {
        return $this->hasMany(EventsImport::class);
    }

    /*
    * get the menus owned by the user
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /*
    * get the monitors owned by the user
     */
    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    /**
     * Interact with the user's address.
     */
    public function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['user_type'] ?? null) === 'admin',
        );
    }

    /**
     * Interact with the user's address.
     */
    public function isRealmAdmin(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => in_array($attributes['user_type'] ?? null, ['realm_admin', 'admin']),
        );
    }
}
