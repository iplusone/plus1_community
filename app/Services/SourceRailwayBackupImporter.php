<?php

namespace App\Services;

use App\Models\RailwayRoute;
use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SourceRailwayBackupImporter
{
    /**
     * @return array{
     *     station_file:string,
     *     route_file:string,
     *     pivot_file:string,
     *     stations:int,
     *     routes:int,
     *     route_stations:int,
     *     nearby_links:int
     * }
     */
    public function import(string $sourceDir, string $prefCode = '12', float $radiusKm = 5.0, bool $dryRun = false): array
    {
        $paths = $this->resolvePaths($sourceDir);
        $stations = $this->loadStations($paths['stations'], $prefCode);
        $pivotRows = $this->loadRouteStations($paths['pivot'], array_keys($stations));
        $routeIds = array_values(array_unique(array_column($pivotRows, 'railway_route_id')));
        $routes = $this->loadRoutes($paths['routes'], $routeIds);

        $result = [
            'station_file' => $paths['stations'],
            'route_file' => $paths['routes'],
            'pivot_file' => $paths['pivot'],
            'stations' => count($stations),
            'routes' => count($routes),
            'route_stations' => count($pivotRows),
            'nearby_links' => 0,
        ];

        if ($dryRun) {
            return $result;
        }

        DB::transaction(function () use (&$result, $stations, $routes, $pivotRows, $radiusKm): void {
            foreach ($stations as $station) {
                DB::table('stations')->updateOrInsert(
                    ['id' => $station['id']],
                    $station
                );
            }

            foreach ($routes as $route) {
                DB::table('railway_routes')->updateOrInsert(
                    ['id' => $route['id']],
                    $route
                );
            }

            foreach ($pivotRows as $pivotRow) {
                DB::table('railway_route_station')->updateOrInsert(
                    [
                        'railway_route_id' => $pivotRow['railway_route_id'],
                        'station_id' => $pivotRow['station_id'],
                    ],
                    $pivotRow
                );
            }

            $result['nearby_links'] = $this->syncNearbyStations(array_keys($stations), $radiusKm);
        });

        return $result;
    }

    /**
     * @return array{stations:string,routes:string,pivot:string}
     */
    private function resolvePaths(string $sourceDir): array
    {
        if (! File::isDirectory($sourceDir)) {
            throw new RuntimeException("バックアップディレクトリが見つかりません: {$sourceDir}");
        }

        return [
            'stations' => $this->latestMatchingFile($sourceDir, 'stations_*.sql'),
            'routes' => $this->latestMatchingFile($sourceDir, 'railway_routes_*.sql'),
            'pivot' => $this->latestMatchingFile($sourceDir, 'railway_route_station_*.sql'),
        ];
    }

    private function latestMatchingFile(string $directory, string $pattern): string
    {
        $matches = glob(rtrim($directory, '/').'/'.$pattern) ?: [];
        sort($matches);

        $path = end($matches);
        if (! is_string($path) || $path === '') {
            throw new RuntimeException("必要なバックアップSQLが見つかりません: {$pattern}");
        }

        return $path;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadStations(string $path, string $prefCode): array
    {
        $stations = [];

        foreach ($this->parseDumpRows($path, 'stations') as $row) {
            $mapped = $this->mapStationRow($row);

            if (($mapped['pref_code'] ?? null) === null || (string) $mapped['pref_code'] !== $prefCode) {
                continue;
            }

            $id = (int) $mapped['id'];
            $stations[$id] = $mapped;
        }

        return $stations;
    }

    /**
     * @param list<mixed> $row
     * @return array<string, mixed>
     */
    private function mapStationRow(array $row): array
    {
        // Source backups from the original project contain extra columns such as
        // display name, line/company codes, address and raw location text.
        if (count($row) >= 16) {
            return [
                'id' => (int) ($row[0] ?? 0),
                'station_name' => $this->nullableString($row[1] ?? null),
                'wikipedia_url' => $this->nullableString($row[2] ?? null),
                'line_name' => $this->nullableString($row[5] ?? null),
                'operator_name' => $this->nullableString($row[6] ?? null),
                'pref_code' => $this->nullableString($row[8] ?? null),
                'longitude' => $this->nullableFloat($row[10] ?? null),
                'latitude' => $this->nullableFloat($row[11] ?? null),
                'location_confirmed' => true,
                'created_at' => $this->nullableString($row[14] ?? null),
                'updated_at' => $this->nullableString($row[15] ?? null),
            ];
        }

        return [
            'id' => (int) ($row[0] ?? 0),
            'station_name' => $this->nullableString($row[1] ?? null),
            'wikipedia_url' => $this->nullableString($row[2] ?? null),
            'line_name' => $this->nullableString($row[3] ?? null),
            'operator_name' => $this->nullableString($row[4] ?? null),
            'pref_code' => $this->nullableString($row[6] ?? null),
            'longitude' => $this->nullableFloat($row[7] ?? null),
            'latitude' => $this->nullableFloat($row[8] ?? null),
            'location_confirmed' => true,
            'created_at' => $this->nullableString($row[11] ?? null),
            'updated_at' => $this->nullableString($row[12] ?? null),
        ];
    }

    /**
     * @param list<int> $routeIds
     * @return array<int, array<string, mixed>>
     */
    private function loadRoutes(string $path, array $routeIds): array
    {
        $routeLookup = array_fill_keys($routeIds, true);
        $routes = [];

        foreach ($this->parseDumpRows($path, 'railway_routes') as $row) {
            $id = (int) $row[0];
            if (! isset($routeLookup[$id])) {
                continue;
            }

            $routes[$id] = [
                'id' => $id,
                'line_name' => $this->nullableString($row[1] ?? null),
                'operator_name' => $this->nullableString($row[2] ?? null),
                'pref_codes' => $this->nullableString($row[3] ?? null),
                'geometry' => $this->nullableString($row[4] ?? null),
                'created_at' => $this->nullableString($row[5] ?? null),
                'updated_at' => $this->nullableString($row[6] ?? null),
            ];
        }

        return $routes;
    }

    /**
     * @param list<int> $stationIds
     * @return list<array<string, mixed>>
     */
    private function loadRouteStations(string $path, array $stationIds): array
    {
        $stationLookup = array_fill_keys($stationIds, true);
        $rows = [];

        foreach ($this->parseDumpRows($path, 'railway_route_station') as $row) {
            $stationId = (int) $row[2];
            if (! isset($stationLookup[$stationId])) {
                continue;
            }

            $rows[] = [
                'railway_route_id' => (int) $row[1],
                'station_id' => $stationId,
                'pivot_order' => isset($row[3]) ? (int) $row[3] : null,
                'created_at' => $this->nullableString($row[4] ?? null),
                'updated_at' => $this->nullableString($row[5] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @return \Generator<int, list<mixed>>
     */
    private function parseDumpRows(string $path, string $table): \Generator
    {
        if (! File::exists($path)) {
            throw new RuntimeException("バックアップSQLが見つかりません: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("バックアップSQLを開けませんでした: {$path}");
        }

        $buffer = '';

        try {
            while (($line = fgets($handle)) !== false) {
                if ($buffer === '' && ! str_starts_with($line, "INSERT INTO `{$table}` VALUES")) {
                    continue;
                }

                $buffer .= $line;

                if (! str_contains($line, ';')) {
                    continue;
                }

                foreach ($this->parseInsertStatement($buffer) as $row) {
                    yield $row;
                }

                $buffer = '';
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<list<mixed>>
     */
    private function parseInsertStatement(string $statement): array
    {
        $valuesPos = strpos($statement, 'VALUES ');
        if ($valuesPos === false) {
            return [];
        }

        $payload = trim(substr($statement, $valuesPos + 7));
        $payload = rtrim($payload, ";\r\n");

        $tuples = [];
        $start = null;
        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($payload);

        for ($index = 0; $index < $length; $index++) {
            $char = $payload[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'") {
                $inString = true;
                continue;
            }

            if ($char === '(') {
                if ($depth === 0) {
                    $start = $index + 1;
                }

                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;

                if ($depth === 0 && $start !== null) {
                    $tuples[] = substr($payload, $start, $index - $start);
                    $start = null;
                }
            }
        }

        return array_map(fn (string $tuple): array => $this->parseTuple($tuple), $tuples);
    }

    /**
     * @return list<mixed>
     */
    private function parseTuple(string $tuple): array
    {
        $values = str_getcsv($tuple, ',', "'", '\\');

        return array_map(function (?string $value): mixed {
            if ($value === null) {
                return null;
            }

            $trimmed = trim($value);

            if (strtoupper($trimmed) === 'NULL') {
                return null;
            }

            return $trimmed;
        }, $values);
    }

    /**
     * @param list<int> $stationIds
     */
    private function syncNearbyStations(array $stationIds, float $radiusKm): int
    {
        if ($stationIds === []) {
            return 0;
        }

        DB::table('station_near_stations')->whereIn('station_id', $stationIds)->delete();

        $stations = Station::query()
            ->whereIn('id', $stationIds)
            ->orderBy('id')
            ->get(['id', 'latitude', 'longitude']);

        $links = 0;

        foreach ($stations as $baseStation) {
            foreach ($stations as $otherStation) {
                if ($baseStation->id === $otherStation->id) {
                    continue;
                }

                $distanceKm = $this->haversine(
                    (float) $baseStation->latitude,
                    (float) $baseStation->longitude,
                    (float) $otherStation->latitude,
                    (float) $otherStation->longitude
                );

                if ($distanceKm > $radiusKm) {
                    continue;
                }

                DB::table('station_near_stations')->updateOrInsert(
                    [
                        'station_id' => $baseStation->id,
                        'near_station_id' => $otherStation->id,
                    ],
                    [
                        'distance_km' => round($distanceKm, 3),
                        'walking_minutes' => (int) ceil(($distanceKm * 1000) / 80),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $links++;
            }
        }

        return $links;
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = $value === null ? null : trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
