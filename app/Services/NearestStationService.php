<?php

namespace App\Services;

use App\Models\Spot;
use Illuminate\Support\Facades\DB;

class NearestStationService
{
    private const LIMIT = 5;

    private const WALKING_SPEED_M_PER_MIN = 80;

    public static function syncForSpot(Spot $spot): void
    {
        if (! $spot->latitude || ! $spot->longitude) {
            return;
        }

        $nearest = self::findNearest(
            $spot->latitude,
            $spot->longitude,
            self::LIMIT,
            PrefectureCodeResolver::resolve($spot->prefecture)
        );

        DB::transaction(function () use ($spot, $nearest) {
            DB::table('spot_stations')->where('spot_id', $spot->id)->delete();

            $rows = $nearest->map(function (object $row, int $index) use ($spot): array {
                $walkingMinutes = (int) ceil($row->distance_km * 1000 / self::WALKING_SPEED_M_PER_MIN);

                return [
                    'spot_id' => $spot->id,
                    'station_id' => $row->id,
                    'distance_km' => round($row->distance_km, 3),
                    'walking_minutes' => $walkingMinutes,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            if ($rows) {
                DB::table('spot_stations')->insert($rows);
            }
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function findNearest(float $lat, float $lng, int $limit, ?string $prefCode = null): \Illuminate\Support\Collection
    {
        return DB::table('stations')
            ->select('id', 'station_name', 'operator_name', 'line_name', 'latitude', 'longitude')
            ->when($prefCode, fn ($query) => $query->where('pref_code', $prefCode))
            ->selectRaw(
                '(6371 * acos(
                    cos(radians(?)) * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km',
                [$lat, $lng, $lat]
            )
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();
    }
}
