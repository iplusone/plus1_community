<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuniFinanceStat extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'year',
        'jis_code',
        'fiscal_power_index',
        'is_three_year_avg',
        'basic_fiscal_need',
        'standard_tax_revenue',
        'local_allocation_tax',
        'is_kofu',
        'source_meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_three_year_avg' => 'boolean',
            'is_kofu' => 'boolean',
            'source_meta' => 'array',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'jis_code', 'jis_code');
    }

    public function getEstimatedIsKofuAttribute(): ?bool
    {
        if (! is_null($this->is_kofu)) {
            return $this->is_kofu;
        }

        if (! is_null($this->local_allocation_tax)) {
            return $this->local_allocation_tax > 0;
        }

        if (! is_null($this->fiscal_power_index)) {
            return $this->fiscal_power_index < 1.0;
        }

        return null;
    }
}
