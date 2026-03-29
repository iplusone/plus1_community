<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Spot;
use App\Models\SpotFeaturedSlot;
use App\Models\SpotSearchDocument;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'portal-admin@example.com'],
            [
                'name' => 'Portal Admin',
                'password' => bcrypt('password'),
            ],
        );

        $genreConsulting = Genre::query()->firstOrCreate(
            ['slug' => 'consulting'],
            ['name' => '相談支援', 'depth' => 1, 'sort_order' => 1],
        );
        $genreEducation = Genre::query()->firstOrCreate(
            ['slug' => 'education'],
            ['name' => '学習教室', 'depth' => 1, 'sort_order' => 2],
        );
        $genreCareer = Genre::query()->firstOrCreate(
            ['slug' => 'career-support'],
            ['name' => 'キャリア支援', 'depth' => 1, 'sort_order' => 3],
        );

        $tagOnline = Tag::query()->firstOrCreate(
            ['slug' => 'online'],
            ['name' => 'オンライン対応'],
        );
        $tagWeekend = Tag::query()->firstOrCreate(
            ['slug' => 'weekend'],
            ['name' => '土日営業'],
        );
        $tagChildcare = Tag::query()->firstOrCreate(
            ['slug' => 'childcare'],
            ['name' => '子育て相談'],
        );

        $hq = Spot::factory()->create([
            'name' => 'Plus1 Community 本部',
            'slug' => 'plus1-community-hq',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'town' => '神南',
            'description' => '企業全体の情報発信と相談受付を担う中心スポットです。',
            'features' => '相談支援、イベント案内、最新ニュース配信',
            'view_count' => 1820,
        ]);

        $branch = Spot::factory()->create([
            'parent_id' => $hq->id,
            'depth' => 2,
            'name' => 'Plus1 Community 横浜拠点',
            'slug' => 'plus1-community-yokohama',
            'prefecture' => '神奈川県',
            'city' => '横浜市',
            'town' => '西区',
            'description' => '地域密着の個別相談とワークショップ開催を行う拠点です。',
            'features' => '駅近、土日営業、地域イベント連携',
            'view_count' => 980,
        ]);

        $school = Spot::factory()->create([
            'parent_id' => $branch->id,
            'depth' => 3,
            'name' => 'Plus1 Community 学習ラボ',
            'slug' => 'plus1-community-learning-lab',
            'prefecture' => '神奈川県',
            'city' => '横浜市',
            'town' => '西区高島',
            'description' => '学習支援とキャリア相談を掛け合わせた教室型スポットです。',
            'features' => '対面講座、個別面談、保護者相談',
            'view_count' => 760,
        ]);

        $draftSpot = Spot::factory()->private()->create([
            'name' => 'Plus1 Community 準備中スポット',
            'slug' => 'plus1-community-draft',
            'prefecture' => '埼玉県',
            'city' => 'さいたま市',
            'town' => '大宮区',
        ]);

        $hq->admins()->syncWithoutDetaching([$admin->id => ['role_scope' => 'all_descendants']]);
        $branch->admins()->syncWithoutDetaching([$admin->id => ['role_scope' => 'self_and_descendants']]);
        $school->admins()->syncWithoutDetaching([$admin->id => ['role_scope' => 'self']]);

        $hq->genres()->syncWithoutDetaching([$genreConsulting->id, $genreCareer->id]);
        $branch->genres()->syncWithoutDetaching([$genreConsulting->id]);
        $school->genres()->syncWithoutDetaching([$genreEducation->id, $genreCareer->id]);

        $hq->tags()->syncWithoutDetaching([$tagOnline->id, $tagWeekend->id]);
        $branch->tags()->syncWithoutDetaching([$tagWeekend->id, $tagChildcare->id]);
        $school->tags()->syncWithoutDetaching([$tagOnline->id]);

        $hq->businessHours()->createMany([
            ['day_of_week' => 1, 'opens_at' => '10:00', 'closes_at' => '19:00', 'is_closed' => false, 'note' => '通常営業'],
            ['day_of_week' => 2, 'opens_at' => '10:00', 'closes_at' => '19:00', 'is_closed' => false, 'note' => '通常営業'],
            ['day_of_week' => 3, 'opens_at' => '10:00', 'closes_at' => '19:00', 'is_closed' => false, 'note' => '通常営業'],
            ['day_of_week' => 4, 'opens_at' => '10:00', 'closes_at' => '19:00', 'is_closed' => false, 'note' => '通常営業'],
            ['day_of_week' => 5, 'opens_at' => '10:00', 'closes_at' => '20:00', 'is_closed' => false, 'note' => '夜間相談対応'],
            ['day_of_week' => 6, 'opens_at' => '11:00', 'closes_at' => '17:00', 'is_closed' => false, 'note' => '予約優先'],
            ['day_of_week' => 0, 'opens_at' => null, 'closes_at' => null, 'is_closed' => true, 'note' => '定休日'],
        ]);

        $consultingService = $hq->services()->create([
            'title' => '相談支援プログラム',
            'description' => '個別相談と伴走型フォローを組み合わせた基本支援です。',
            'sort_order' => 1,
        ]);
        $careerService = $hq->services()->create([
            'title' => 'キャリア相談',
            'description' => '進路相談、就職相談、リスキリング計画づくりを支援します。',
            'sort_order' => 2,
        ]);
        $consultingService->menus()->createMany([
            ['spot_id' => $hq->id, 'name' => '初回相談', 'description' => 'ヒアリングと課題整理', 'price_text' => '無料', 'sort_order' => 1],
            ['spot_id' => $hq->id, 'name' => '継続伴走プラン', 'description' => '月次面談とアクション設計', 'price_text' => '月額 9,800円', 'sort_order' => 2],
        ]);
        $careerService->menus()->createMany([
            ['spot_id' => $hq->id, 'name' => 'キャリア棚卸し', 'description' => 'スキル可視化と方向性整理', 'price_text' => '5,500円', 'sort_order' => 1],
        ]);

        $branch->services()->create([
            'title' => '地域ワークショップ',
            'description' => '少人数の対話型イベントと地域連携プログラムを実施します。',
            'sort_order' => 1,
        ]);

        $schoolService = $school->services()->create([
            'title' => '学習・進路サポート',
            'description' => '学習支援、面談、保護者相談をワンストップで提供します。',
            'sort_order' => 1,
        ]);
        $schoolService->menus()->createMany([
            ['spot_id' => $school->id, 'name' => '個別学習コース', 'description' => '1対1の学習伴走', 'price_text' => '月額 12,000円', 'sort_order' => 1],
            ['spot_id' => $school->id, 'name' => '保護者面談', 'description' => '進路相談と家庭内サポートの整理', 'price_text' => '1回 3,000円', 'sort_order' => 2],
        ]);

        $hq->staff()->createMany([
            ['name' => '山田 彩', 'profile' => '相談支援コーディネーター。地域連携と伴走支援を担当。', 'sort_order' => 1],
            ['name' => '中村 亮', 'profile' => 'キャリアアドバイザー。進路相談と就労支援を担当。', 'sort_order' => 2],
        ]);
        $school->staff()->createMany([
            ['name' => '佐藤 真紀', 'profile' => '学習コーチ。対面学習と保護者面談を担当。', 'sort_order' => 1],
        ]);

        $hq->coupons()->create([
            'title' => '初回相談無料',
            'content' => '登録月は初回相談を無料で利用できます。',
            'conditions' => '新規登録企業限定',
            'starts_at' => now()->subWeek(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ]);
        $school->coupons()->create([
            'title' => '保護者面談割引',
            'content' => '初回の保護者面談を 50% オフで案内します。',
            'conditions' => '学習コース体験申込者',
            'starts_at' => now()->subDays(3),
            'expires_at' => now()->addWeeks(2),
            'is_active' => true,
        ]);

        $hq->media()->createMany([
            ['type' => 'image', 'path' => 'demo/hq-lounge.jpg', 'caption' => '相談ラウンジ', 'sort_order' => 1],
            ['type' => 'image', 'path' => 'demo/hq-seminar.jpg', 'caption' => 'セミナースペース', 'sort_order' => 2],
        ]);
        $school->media()->create([
            'type' => 'image',
            'path' => 'demo/school-classroom.jpg',
            'caption' => '学習スペース',
            'sort_order' => 1,
        ]);

        $hq->wordpressSite()->create([
            'base_url' => 'https://example.com/plus1-community-hq',
            'api_base_url' => 'https://example.com/plus1-community-hq/wp-json/wp/v2',
            'is_active' => true,
            'last_synced_at' => now()->subHours(6),
        ]);

        SpotFeaturedSlot::query()->create([
            'spot_id' => $hq->id,
            'slot_type' => 'featured',
            'sort_order' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
        SpotFeaturedSlot::query()->create([
            'spot_id' => $branch->id,
            'slot_type' => 'featured',
            'sort_order' => 2,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
        SpotFeaturedSlot::query()->create([
            'spot_id' => $school->id,
            'slot_type' => 'featured',
            'sort_order' => 3,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        foreach ([$hq, $branch, $school, $draftSpot] as $spot) {
            SpotSearchDocument::query()->updateOrCreate(
                ['spot_id' => $spot->id],
                [
                    'spot_name' => $spot->name,
                    'prefecture' => $spot->prefecture,
                    'city' => $spot->city,
                    'town' => $spot->town,
                    'full_address' => trim(implode(' ', array_filter([
                        $spot->prefecture,
                        $spot->city,
                        $spot->town,
                        $spot->address_line,
                    ]))),
                    'genre_names' => $spot->genres()->pluck('name')->values()->all(),
                    'genre_paths' => $spot->genres()->pluck('slug')->map(
                        fn (string $slug): string => Str::of($slug)->replace('-', ' > ')->value()
                    )->values()->all(),
                    'tag_names' => $spot->tags()->pluck('name')->values()->all(),
                    'is_public' => $spot->is_public,
                    'published_at' => $spot->published_at,
                    'view_count' => $spot->view_count,
                    'thumbnail_url' => null,
                    'indexed_at' => now(),
                ],
            );
        }
    }
}
