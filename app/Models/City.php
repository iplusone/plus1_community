<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pref_id',
        'name',
        'kana',
        'code',
        'lat',
        'lng',
        'latlng_confirmed',
        'homepage_url',
        'gikai_url',
        'giji_checked_at',
        'giji_presence',
        'giji_types',
        'giji_confidence',
        'giji_sample_url',
        'giji_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pref_id' => 'integer',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'latlng_confirmed' => 'boolean',
            'giji_checked_at' => 'datetime',
        ];
    }

    public function prefecture(): BelongsTo
    {
        return $this->belongsTo(Prefecture::class, 'pref_id');
    }
}
