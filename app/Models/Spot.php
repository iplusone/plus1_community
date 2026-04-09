<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Spot extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'parent_id',
        'depth',
        'name',
        'slug',
        'postal_code',
        'prefecture',
        'city',
        'town',
        'address_line',
        'phone',
        'description',
        'features',
        'access_text',
        'business_hours_text',
        'holiday_text',
        'thumbnail_path',
        'nearest_station_max_walking_minutes',
        'latitude',
        'longitude',
        'is_public',
        'published_at',
        'view_count',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'published_at' => 'datetime',
            'nearest_station_max_walking_minutes' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where(function (Builder $builder) {
                $builder->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $descendantIds = [];
        $pendingIds = [$this->id];

        while ($pendingIds !== []) {
            $childIds = self::query()
                ->whereIn('parent_id', $pendingIds)
                ->pluck('id')
                ->all();

            if ($childIds === []) {
                break;
            }

            $descendantIds = [...$descendantIds, ...$childIds];
            $pendingIds = $childIds;
        }

        return array_values(array_unique($descendantIds));
    }

    public function hierarchyLabel(): string
    {
        $segments = [$this->name];
        $current = $this->parent;
        $guard = 0;

        while ($current && $guard < 5) {
            array_unshift($segments, $current->name);
            $current = $current->parent;
            $guard++;
        }

        return implode(' > ', $segments);
    }

    public function cardImageUrl(): ?string
    {
        $candidate = $this->thumbnail_path;

        if (! $candidate) {
            $candidate = $this->media
                ->pluck('thumbnail_path')
                ->filter()
                ->first()
                ?? $this->media
                    ->pluck('path')
                    ->filter()
                    ->first();
        }

        if (! $candidate) {
            return null;
        }

        if (Str::startsWith($candidate, ['http://', 'https://', 'data:'])) {
            return $candidate;
        }

        if (Str::startsWith($candidate, ['/storage/', 'storage/'])) {
            return Str::startsWith($candidate, '/')
                ? $candidate
                : '/'.$candidate;
        }

        $candidate = Str::replaceFirst('public/', '', ltrim($candidate, '/'));

        return '/storage/'.$candidate;
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'spot_admins')
            ->withPivot('role_scope')
            ->withTimestamps();
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'spot_genres')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'spot_tags')->withTimestamps();
    }

    public function searchDocument(): HasOne
    {
        return $this->hasOne(SpotSearchDocument::class);
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(SpotBusinessHour::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(SpotService::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(SpotMenu::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(SpotMedia::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(SpotStaff::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(SpotCoupon::class);
    }

    public function wordpressSite(): HasOne
    {
        return $this->hasOne(SpotWordpressSite::class);
    }

    public function featuredSlots(): HasMany
    {
        return $this->hasMany(SpotFeaturedSlot::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(SpotPageView::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'spot_stations')
            ->withPivot(['distance_km', 'walking_minutes', 'sort_order'])
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    public function spotStations(): HasMany
    {
        return $this->hasMany(SpotStation::class);
    }
}
