<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Station;
use Database\Seeders\PrefecturesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearestStationPrefecturePriorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PrefecturesTableSeeder::class);
    }

    public function test_nearest_station_sync_prefers_same_prefecture_stations(): void
    {
        $wrongPrefStation = Station::query()->create([
            'station_name' => '刈谷',
            'operator_name' => '東海旅客鉄道',
            'pref_code' => '23',
            'latitude' => 35.300000,
            'longitude' => 140.000000,
        ]);

        $correctPrefStation = Station::query()->create([
            'station_name' => '西大原',
            'operator_name' => 'いすみ鉄道',
            'pref_code' => '12',
            'latitude' => 35.301000,
            'longitude' => 140.001000,
        ]);

        $spot = Spot::query()->create([
            'name' => 'いすみ市役所テスト',
            'slug' => 'isumi-pref-priority-test',
            'prefecture' => '千葉県',
            'city' => 'いすみ市',
            'latitude' => 35.300000,
            'longitude' => 140.000000,
            'is_public' => true,
            'view_count' => 0,
            'sort_order' => 0,
        ]);

        $stationIds = $spot->spotStations()->pluck('station_id')->all();

        $this->assertContains($correctPrefStation->id, $stationIds);
        $this->assertNotContains($wrongPrefStation->id, $stationIds);
    }
}
