<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RailwayRoute extends Model
{
    protected $fillable = [
        'line_name',
        'operator_name',
        'pref_codes',
        'geometry',
    ];

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'railway_route_station', 'railway_route_id', 'station_id')
            ->withPivot('pivot_order')
            ->orderByPivot('pivot_order')
            ->withTimestamps();
    }
}
