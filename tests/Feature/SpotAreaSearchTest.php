<?php

namespace Tests\Feature;

use App\Models\RailwayRoute;
use App\Models\Spot;
use App\Models\Station;
use App\Models\StationNearStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpotAreaSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $attributes
     */
    private function createSpot(array $attributes): Spot
    {
        return Spot::query()->create($attributes + [
            'is_public' => true,
            'published_at' => now()->subMinute(),
            'view_count' => 0,
            'sort_order' => 0,
        ]);
    }

    public function test_station_area_search_includes_nearby_stations(): void
    {
        $baseStation = Station::query()->create([
            'station_name' => '渋谷',
            'operator_name' => '東急',
            'longitude' => 139.701,
            'latitude' => 35.658,
        ]);

        $nearStation = Station::query()->create([
            'station_name' => '表参道',
            'operator_name' => '東京メトロ',
            'longitude' => 139.712,
            'latitude' => 35.665,
        ]);

        StationNearStation::query()->create([
            'station_id' => $baseStation->id,
            'near_station_id' => $nearStation->id,
            'distance_km' => 0.9,
            'walking_minutes' => 12,
        ]);

        $spot = $this->createSpot([
            'name' => '近隣駅スポット',
            'slug' => 'nearby-station-spot',
        ]);

        DB::table('spot_stations')->insert([
            'spot_id' => $spot->id,
            'station_id' => $nearStation->id,
            'distance_km' => 0.9,
            'walking_minutes' => 12,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('spots.index', ['area' => '[駅] 渋谷']));

        $response->assertOk();
        $response->assertSee('近隣駅スポット');
    }

    public function test_route_area_search_matches_all_stations_on_the_route(): void
    {
        $route = RailwayRoute::query()->create([
            'line_name' => '東急東横線',
            'operator_name' => '東急',
        ]);

        $stationA = Station::query()->create([
            'station_name' => '渋谷',
            'operator_name' => '東急',
            'longitude' => 139.701,
            'latitude' => 35.658,
        ]);

        $stationB = Station::query()->create([
            'station_name' => '代官山',
            'operator_name' => '東急',
            'longitude' => 139.703,
            'latitude' => 35.649,
        ]);

        $route->stations()->attach($stationA->id, ['order' => 1]);
        $route->stations()->attach($stationB->id, ['order' => 2]);

        $spot = $this->createSpot([
            'name' => '沿線スポット',
            'slug' => 'route-spot',
        ]);

        DB::table('spot_stations')->insert([
            'spot_id' => $spot->id,
            'station_id' => $stationB->id,
            'distance_km' => 0.4,
            'walking_minutes' => 5,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('spots.index', ['area' => '[路線] 東急東横線']));

        $response->assertOk();
        $response->assertSee('沿線スポット');
    }
}
