<?php

namespace Tests\Feature\Admin;

use App\Models\Spot;
use App\Models\SpotCoupon;
use App\Models\SpotStaff;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $attributes
     */
    private function createSpot(array $attributes = []): Spot
    {
        return Spot::query()->create($attributes + [
            'name' => '管理テスト拠点',
            'slug' => 'admin-test-spot',
            'is_public' => false,
            'view_count' => 0,
            'sort_order' => 0,
        ]);
    }

    public function test_staff_can_be_created_from_admin_screen(): void
    {
        $spot = $this->createSpot();

        $response = $this->post(route('admin.spots.staff.store', $spot), [
            'name' => '山田 花子',
            'profile' => 'カウンセリング担当',
            'sort_order' => 2,
        ]);

        $response->assertRedirect(route('admin.spots.staff.index', $spot));

        $this->assertDatabaseHas(SpotStaff::class, [
            'spot_id' => $spot->id,
            'name' => '山田 花子',
            'profile' => 'カウンセリング担当',
            'sort_order' => 2,
        ]);
    }

    public function test_coupon_store_defaults_is_active_to_false_when_checkbox_is_unchecked(): void
    {
        $spot = $this->createSpot(['slug' => 'coupon-test-spot']);

        $response = $this->post(route('admin.spots.coupons.store', $spot), [
            'title' => '春のキャンペーン',
            'content' => '初回限定割引',
            'conditions' => '予約必須',
        ]);

        $response->assertRedirect(route('admin.spots.coupons.index', $spot));

        $coupon = SpotCoupon::query()->where('spot_id', $spot->id)->firstOrFail();

        $this->assertFalse($coupon->is_active);
    }

    public function test_station_can_be_added_manually_and_duplicate_is_rejected(): void
    {
        $spot = $this->createSpot(['slug' => 'station-test-spot']);
        $station = Station::query()->create([
            'station_name' => '渋谷',
            'operator_name' => '東急',
            'longitude' => 139.701,
            'latitude' => 35.658,
        ]);

        $createResponse = $this->post(route('admin.spots.stations.store', $spot), [
            'station_name' => '渋谷',
            'walking_minutes' => 5,
        ]);

        $createResponse->assertRedirect(route('admin.spots.stations.index', $spot));
        $this->assertDatabaseHas('spot_stations', [
            'spot_id' => $spot->id,
            'station_id' => $station->id,
            'walking_minutes' => 5,
        ]);

        $duplicateResponse = $this->from(route('admin.spots.stations.index', $spot))
            ->post(route('admin.spots.stations.store', $spot), [
                'station_name' => '渋谷',
                'walking_minutes' => 6,
            ]);

        $duplicateResponse->assertRedirect(route('admin.spots.stations.index', $spot));
        $duplicateResponse->assertSessionHasErrors([
            'station_name' => 'その駅はすでに登録されています。',
        ]);
    }

    public function test_station_recalculate_requires_latitude_and_longitude(): void
    {
        $spot = $this->createSpot(['slug' => 'recalc-test-spot']);

        $response = $this->from(route('admin.spots.stations.index', $spot))
            ->post(route('admin.spots.stations.recalculate', $spot));

        $response->assertRedirect(route('admin.spots.stations.index', $spot));
        $response->assertSessionHasErrors([
            'recalculate' => '緯度・経度が未設定です。先にスポット基本情報で位置情報を入力してください。',
        ]);
    }
}
