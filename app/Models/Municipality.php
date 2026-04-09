<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    protected $primaryKey = 'jis_code';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'jis_code',
        'pref_code',
        'pref_name',
        'city_name',
        'city_kana',
    ];

    public function financeStats(): HasMany
    {
        return $this->hasMany(MuniFinanceStat::class, 'jis_code', 'jis_code');
    }
}
