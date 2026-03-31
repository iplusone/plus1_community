<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotSearchDocument extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'spot_id',
        'spot_name',
        'prefecture',
        'city',
        'town',
        'full_address',
        'genre_names',
        'genre_paths',
        'tag_names',
        'is_public',
        'published_at',
        'view_count',
        'thumbnail_url',
        'indexed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'genre_names' => 'array',
            'genre_paths' => 'array',
            'tag_names' => 'array',
            'is_public' => 'boolean',
            'published_at' => 'datetime',
            'indexed_at' => 'datetime',
        ];
    }

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }

    public static function syncForSpot(Spot $spot): void
    {
        $spot->loadMissing(['genres', 'tags']);

        static::updateOrCreate(
            ['spot_id' => $spot->id],
            [
                'spot_name' => $spot->name,
                'prefecture' => $spot->prefecture,
                'city' => $spot->city,
                'town' => $spot->town,
                'full_address' => collect([$spot->prefecture, $spot->city, $spot->town, $spot->address_line])
                    ->filter()->join(''),
                'genre_names' => $spot->genres->pluck('name')->all(),
                'genre_paths' => $spot->genres->pluck('name')->all(),
                'tag_names' => $spot->tags->pluck('name')->all(),
                'is_public' => (bool) $spot->is_public,
                'published_at' => $spot->published_at,
                'view_count' => $spot->view_count ?? 0,
                'thumbnail_url' => $spot->thumbnail_path,
                'indexed_at' => now(),
            ]
        );
    }
}
