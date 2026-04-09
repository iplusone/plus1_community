<?php

namespace App\Services;

use App\Models\Prefecture;

class PrefectureCodeResolver
{
    public static function resolve(?string $prefectureName): ?string
    {
        $name = trim((string) $prefectureName);

        if ($name === '') {
            return null;
        }

        return Prefecture::query()
            ->where('name', $name)
            ->value('code');
    }
}
