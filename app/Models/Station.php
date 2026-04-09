<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    protected $fillable = [
        'station_name',
        'wikipedia_url',
        'line_name',
        'operator_name',
        'pref_code',
        'longitude',
        'latitude',
        'location_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'location_confirmed' => 'boolean',
        ];
    }

    public function spots(): BelongsToMany
    {
        return $this->belongsToMany(Spot::class, 'spot_stations')
            ->withPivot(['distance_km', 'walking_minutes', 'sort_order'])
            ->withTimestamps();
    }

    public function railwayRoutes(): BelongsToMany
    {
        return $this->belongsToMany(RailwayRoute::class, 'railway_route_station', 'station_id', 'railway_route_id')
            ->withPivot('pivot_order')
            ->withTimestamps();
    }

    public function nearStations(): HasMany
    {
        return $this->hasMany(StationNearStation::class, 'station_id');
    }
}
