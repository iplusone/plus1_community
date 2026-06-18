<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlitSpot extends Model
{
    protected $fillable = [
        'dataset_code',
        'source_id',
        'category',
        'sub_category',
        'name',
        'pref_code',
        'admin_area_code',
        'address',
        'latitude',
        'longitude',
        'attributes',
        'source_year',
        'imported_at',
    ];

    protected $casts = [
        'attributes' => 'array',
        'latitude'   => 'float',
        'longitude'  => 'float',
        'imported_at' => 'datetime',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(MlitDataset::class, 'dataset_code', 'code');
    }

    /** 指定半径（km）以内のスポットを返す（簡易 Haversine） */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm = 1.0): Builder
    {
        $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $radiusKm])
            ->orderBy('distance');
    }

    public function scopeByDataset(Builder $query, string $code): Builder
    {
        return $query->where('dataset_code', $code);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByPref(Builder $query, string $prefCode): Builder
    {
        return $query->where('pref_code', $prefCode);
    }
}
