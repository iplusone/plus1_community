<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MlitDataset extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'license',
        'download_url',
        'source_year',
        'last_imported_at',
        'record_count',
    ];

    protected $casts = [
        'last_imported_at' => 'datetime',
    ];

    public function spots(): HasMany
    {
        return $this->hasMany(MlitSpot::class, 'dataset_code', 'code');
    }
}
