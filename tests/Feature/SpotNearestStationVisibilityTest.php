<?php

namespace Tests\Feature;

use App\Models\RailwayRoute;
use App\Models\Spot;
use App\Models\SpotStation;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotNearestStationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_spot_detail_shows_only_stations_within_walking_limit(): void
    {
        $spot = Spot::factory()->create([
            'name' => '千葉テスト拠点',
            'slug' => 'chiba-test-spot',
            'nearest_station_max_walking_minutes' => 30,
        ]);

        $visibleStation = Station::query()->create([
            'station_name' => '袖ケ浦',
            'operator_name' => 'JR東日本',
            'latitude' => 35.429,
            'longitude' => 139.954,
        ]);

        $hiddenStation = Station::query()->create([
            'station_name' => '長浦',
            'operator_name' => 'JR東日本',
            'latitude' => 35.464,
            'longitude' => 139.995,
        ]);

        RailwayRoute::query()->create([
            'operator_name' => 'JR東日本',
            'line_name' => '内房線',
        ])->stations()->attach($visibleStation->id, ['order' => 1]);

        RailwayRoute::query()->create([
            'operator_name' => 'JR東日本',
            'line_name' => '内房線',
        ])->stations()->attach($hiddenStation->id, ['order' => 1]);

        SpotStation::query()->create([
            'spot_id' => $spot->id,
            'station_id' => $visibleStation->id,
            'walking_minutes' => 16,
            'sort_order' => 1,
        ]);

        SpotStation::query()->create([
            'spot_id' => $spot->id,
            'station_id' => $hiddenStation->id,
            'walking_minutes' => 42,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('spots.show', $spot));

        $response->assertOk();
        $response->assertSee('袖ケ浦駅');
        $response->assertSee('徒歩 16 分');
        $response->assertDontSee('長浦駅');
        $response->assertDontSee('徒歩 42 分');
    }
}
