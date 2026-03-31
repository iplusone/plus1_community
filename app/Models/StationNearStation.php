<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationNearStation extends Model
{
    protected $fillable = [
        'station_id',
        'near_station_id',
        'distance_km',
        'walking_minutes',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function nearStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'near_station_id');
    }
}
