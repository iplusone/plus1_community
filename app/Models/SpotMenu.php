<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotMenu extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'spot_id',
        'spot_service_id',
        'name',
        'description',
        'price_text',
        'sort_order',
    ];

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SpotService::class, 'spot_service_id');
    }
}
