<?php

namespace App\Models;

use Database\Factories\StationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name'])]
class Station extends Model
{
    /** @use HasFactory<StationFactory> */
    use HasFactory;

    /** @return HasMany<Connection, $this> */
    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'origin_station_id');
    }

    /** @return HasMany<Connection, $this> */
    public function incomingConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'destination_station_id');
    }
}
