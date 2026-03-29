<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpotService extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'spot_id',
        'title',
        'description',
        'sort_order',
    ];

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(SpotMenu::class);
    }
}
