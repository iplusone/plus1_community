<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by_user_id');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(self::class, 'created_by_user_id');
    }

    public function adminSpots(): BelongsToMany
    {
        return $this->belongsToMany(Spot::class, 'spot_admins')
            ->withPivot('role_scope')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, Spot>
     */
    public function manageableSpots(): Collection
    {
        $ids = $this->manageableSpotIds();

        if ($ids === []) {
            return new Collection();
        }

        return Spot::query()
            ->whereIn('id', $ids)
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<int>
     */
    public function manageableSpotIds(): array
    {
        if (! $this->company_id) {
            return [];
        }

        $assignedSpots = $this->adminSpots()
            ->where('spots.company_id', $this->company_id)
            ->get();

        if ($assignedSpots->isEmpty()) {
            return Spot::query()
                ->where('company_id', $this->company_id)
                ->pluck('id')
                ->all();
        }

        $ids = [];

        foreach ($assignedSpots as $spot) {
            $ids[] = $spot->id;

            if (in_array($spot->pivot->role_scope, ['self_and_descendants', 'all_descendants'], true)) {
                $ids = [...$ids, ...$spot->descendantIds()];
            }
        }

        return array_values(array_unique($ids));
    }
}
