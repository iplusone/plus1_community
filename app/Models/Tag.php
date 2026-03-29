<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'usage_count',
    ];

    public function spots(): BelongsToMany
    {
        return $this->belongsToMany(Spot::class, 'spot_tags')->withTimestamps();
    }
}
