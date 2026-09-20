<?php

namespace App\Traits;

use App\Models\Realm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait RealmTrait
{
    /**
     * Scope a query to only include elements of a given realm.
     */
    public function scopeOfRealm(Builder $query, int $id): void
    {
        $query->where('realm_id', $id);
    }

    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }
}
