<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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

    public function assetUrl(): ?string
    {
        return $this->normalizeMediaPath($this->path);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->normalizeMediaPath($this->thumbnail_path ?: $this->path);
    }

    public function youtubeEmbedUrl(): ?string
    {
        if ($this->type !== 'video') {
            return null;
        }

        $path = trim((string) $this->path);

        if ($path === '') {
            return null;
        }

        if (Str::contains($path, ['youtube.com/embed/', 'youtube-nocookie.com/embed/'])) {
            return $path;
        }

        return null;
    }

    private function normalizeMediaPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return Str::startsWith($path, '/')
                ? $path
                : '/'.$path;
        }

        $path = Str::replaceFirst('public/', '', ltrim($path, '/'));

        return '/storage/'.$path;
    }
}
