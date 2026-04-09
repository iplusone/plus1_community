<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prefecture extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'kana',
        'region',
        'code',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'pref_id');
    }
}
