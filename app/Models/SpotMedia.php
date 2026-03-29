<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotMedia extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'spot_id',
        'type',
        'path',
        'thumbnail_path',
        'caption',
        'sort_order',
    ];

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }
}
