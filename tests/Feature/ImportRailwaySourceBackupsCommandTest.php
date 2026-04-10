<?php

namespace Tests\Feature;

use App\Models\RailwayRoute;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportRailwaySourceBackupsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_chiba_subset_from_source_backups(): void
    {
        $directory = storage_path('app/testing/railway-backups');
        File::ensureDirectoryExists($directory);

        file_put_contents($directory.'/stations_20250619_060027.sql', implode("\n", [
            "INSERT INTO `stations` VALUES ".
            "(1,'千葉','https://example.com/chiba','千葉駅','0','総武線','JR東日本','11','12',NULL,140.123400,35.612300,'千葉県千葉市','raw','2025-05-18 07:22:20','2025-05-26 07:25:36'),".
            "(2,'西千葉',NULL,'西千葉駅','0','総武線','JR東日本','11','12',NULL,140.103400,35.622300,'千葉県千葉市','raw','2025-05-18 07:22:20','2025-05-26 07:25:36'),".
            "(3,'渋谷',NULL,'渋谷駅','0','山手線','JR東日本','11','13',NULL,139.701000,35.658000,'東京都渋谷区','raw','2025-05-18 07:22:20','2025-05-26 07:25:36');",
        ]));

        file_put_contents($directory.'/railway_routes_20250619_060027.sql', implode("\n", [
            "INSERT INTO `railway_routes` VALUES ".
            "(10,'総武線','JR東日本','12','{\"type\":\"LineString\",\"coordinates\":[[140.1,35.6],[140.2,35.7]]}','2025-05-17 11:39:29','2025-05-27 14:59:44'),".
            "(11,'山手線','JR東日本','13','{\"type\":\"LineString\",\"coordinates\":[[139.7,35.6],[139.8,35.7]]}','2025-05-17 11:39:29','2025-05-27 14:59:44');",
        ]));

        file_put_contents($directory.'/railway_route_station_20250619_060027.sql', implode("\n", [
            "INSERT INTO `railway_route_station` VALUES ".
            "(100,10,1,1,'2025-05-25 10:31:32','2025-05-25 10:31:32'),".
            "(101,10,2,2,'2025-05-25 10:31:32','2025-05-25 10:31:32'),".
            "(102,11,3,1,'2025-05-25 10:31:32','2025-05-25 10:31:32');",
        ]));

        Artisan::call('railway:import-source-backups', [
            'source_dir' => $directory,
            '--pref-code' => '12',
            '--radius' => 5,
        ]);

        $this->assertSame(2, Station::query()->count());
        $this->assertDatabaseHas('stations', [
            'id' => 1,
            'station_name' => '千葉',
            'pref_code' => '12',
        ]);
        $this->assertDatabaseHas('stations', [
            'id' => 2,
            'station_name' => '西千葉',
            'pref_code' => '12',
        ]);
        $this->assertDatabaseMissing('stations', [
            'id' => 3,
        ]);

        $route = RailwayRoute::query()->find(10);
        $this->assertNotNull($route);
        $this->assertSame('総武線', $route->line_name);
        $this->assertSame(2, $route->stations()->count());
        $this->assertDatabaseMissing('railway_routes', [
            'id' => 11,
        ]);

        $this->assertDatabaseHas('railway_route_station', [
            'railway_route_id' => 10,
            'station_id' => 1,
            'pivot_order' => 1,
        ]);
        $this->assertDatabaseHas('railway_route_station', [
            'railway_route_id' => 10,
            'station_id' => 2,
            'pivot_order' => 2,
        ]);

        $this->assertSame(2, DB::table('station_near_stations')->count());
        $this->assertDatabaseHas('station_near_stations', [
            'station_id' => 1,
            'near_station_id' => 2,
        ]);
    }

    public function test_it_supports_dry_run(): void
    {
        $directory = storage_path('app/testing/railway-backups-dry-run');
        File::ensureDirectoryExists($directory);

        file_put_contents($directory.'/stations_20250619_060027.sql', "INSERT INTO `stations` VALUES (1,'千葉',NULL,'千葉駅','0','総武線','JR東日本','11','12',NULL,140.123400,35.612300,'千葉県千葉市','raw','2025-05-18 07:22:20','2025-05-26 07:25:36');\n");
        file_put_contents($directory.'/railway_routes_20250619_060027.sql', "INSERT INTO `railway_routes` VALUES (10,'総武線','JR東日本','12',NULL,'2025-05-17 11:39:29','2025-05-27 14:59:44');\n");
        file_put_contents($directory.'/railway_route_station_20250619_060027.sql', "INSERT INTO `railway_route_station` VALUES (100,10,1,1,'2025-05-25 10:31:32','2025-05-25 10:31:32');\n");

        Artisan::call('railway:import-source-backups', [
            'source_dir' => $directory,
            '--pref-code' => '12',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, Station::query()->count());
        $this->assertSame(0, RailwayRoute::query()->count());
        $this->assertSame(0, DB::table('railway_route_station')->count());
    }
}
