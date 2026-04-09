<?php

namespace Tests\Feature\Admin;

use App\Models\Spot;
use App\Models\SpotCoupon;
use App\Models\SpotMedia;
use App\Models\SpotStaff;
use App\Models\Station;
use Database\Seeders\PrefecturesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpotAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PrefecturesTableSeeder::class);
    }

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

    public function test_station_manual_add_prefers_same_prefecture_when_station_names_overlap(): void
    {
        $spot = $this->createSpot([
            'slug' => 'station-pref-priority-test-spot',
            'prefecture' => '千葉県',
        ]);

        $chibaStation = Station::query()->create([
            'station_name' => '刈谷',
            'operator_name' => 'いすみ鉄道',
            'pref_code' => '12',
            'longitude' => 140.0,
            'latitude' => 35.3,
        ]);

        Station::query()->create([
            'station_name' => '刈谷',
            'operator_name' => '東海旅客鉄道',
            'pref_code' => '23',
            'longitude' => 136.9,
            'latitude' => 35.0,
        ]);

        $response = $this->post(route('admin.spots.stations.store', $spot), [
            'station_name' => '刈谷',
            'walking_minutes' => 17,
        ]);

        $response->assertRedirect(route('admin.spots.stations.index', $spot));

        $this->assertDatabaseHas('spot_stations', [
            'spot_id' => $spot->id,
            'station_id' => $chibaStation->id,
            'walking_minutes' => 17,
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

    public function test_spot_can_save_nearest_station_max_walking_minutes_from_admin_form(): void
    {
        $spot = $this->createSpot(['slug' => 'walking-limit-test-spot']);

        $response = $this->put(route('admin.spots.update', $spot), [
            'name' => '管理テスト拠点',
            'slug' => 'walking-limit-test-spot',
            'prefecture' => '千葉県',
            'city' => '千葉市',
            'nearest_station_max_walking_minutes' => 20,
            'is_public' => '1',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.spots.index'));

        $this->assertDatabaseHas('spots', [
            'id' => $spot->id,
            'nearest_station_max_walking_minutes' => 20,
        ]);
    }

    public function test_video_store_normalizes_youtube_embed_tag(): void
    {
        $spot = $this->createSpot(['slug' => 'media-test-spot']);

        $response = $this->post(route('admin.spots.media.store', $spot), [
            'type' => 'video',
            'path' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/abc123XYZ90" title="YouTube video player"></iframe>',
            'caption' => '紹介動画',
        ]);

        $response->assertRedirect(route('admin.spots.media.index', ['spot' => $spot, 'type' => 'video']));

        $this->assertDatabaseHas(SpotMedia::class, [
            'spot_id' => $spot->id,
            'type' => 'video',
            'path' => 'https://www.youtube.com/embed/abc123XYZ90',
            'caption' => '紹介動画',
        ]);
    }

    public function test_image_limit_is_ten_items(): void
    {
        $spot = $this->createSpot(['slug' => 'image-limit-spot']);

        $spot->media()->createMany(collect(range(1, 10))->map(fn (int $index) => [
            'spot_id' => $spot->id,
            'type' => 'image',
            'path' => "storage/media/sample-{$index}.jpg",
            'caption' => "画像{$index}",
            'sort_order' => $index,
        ])->all());

        $response = $this->from(route('admin.spots.media.index', $spot))
            ->post(route('admin.spots.media.store', $spot), [
                'type' => 'image',
                'path' => 'storage/media/overflow.jpg',
            ]);

        $response->assertRedirect(route('admin.spots.media.index', $spot));
        $response->assertSessionHasErrors([
            'type' => '画像は10枚まで登録できます。',
        ]);
    }

    public function test_video_limit_is_five_items(): void
    {
        $spot = $this->createSpot(['slug' => 'video-limit-spot']);

        $spot->media()->createMany(collect(range(1, 5))->map(fn (int $index) => [
            'spot_id' => $spot->id,
            'type' => 'video',
            'path' => "https://www.youtube.com/embed/video{$index}",
            'caption' => "動画{$index}",
            'sort_order' => $index,
        ])->all());

        $response = $this->from(route('admin.spots.media.index', $spot))
            ->post(route('admin.spots.media.store', $spot), [
                'type' => 'video',
                'path' => '<iframe src="https://www.youtube.com/embed/overflow001"></iframe>',
            ]);

        $response->assertRedirect(route('admin.spots.media.index', $spot));
        $response->assertSessionHasErrors([
            'type' => '動画は5件まで登録できます。',
        ]);
    }

    public function test_image_can_be_uploaded_from_admin_screen(): void
    {
        Storage::fake('public');

        $spot = $this->createSpot(['slug' => 'image-upload-spot']);

        $response = $this->post(route('admin.spots.media.store', $spot), [
            'type' => 'image',
            'caption' => '外観写真',
            'uploaded_image' => UploadedFile::fake()->image('spot.jpg', 1200, 800),
        ]);

        $response->assertRedirect(route('admin.spots.media.index', ['spot' => $spot, 'type' => 'image']));

        $media = SpotMedia::query()->where('spot_id', $spot->id)->firstOrFail();

        $this->assertSame('image', $media->type);
        $this->assertSame('外観写真', $media->caption);
        $this->assertNotNull($media->path);
        $this->assertSame($media->path, $media->thumbnail_path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_multiple_images_can_be_uploaded_from_admin_screen(): void
    {
        Storage::fake('public');

        $spot = $this->createSpot(['slug' => 'multi-image-upload-spot']);

        $response = $this->post(route('admin.spots.media.store', $spot), [
            'type' => 'image',
            'uploaded_images' => [
                UploadedFile::fake()->image('spot-1.jpg', 1200, 800),
                UploadedFile::fake()->image('spot-2.jpg', 1200, 800),
            ],
        ]);

        $response->assertRedirect(route('admin.spots.media.index', ['spot' => $spot, 'type' => 'image']));
        $this->assertSame(2, $spot->media()->where('type', 'image')->count());
    }
}
