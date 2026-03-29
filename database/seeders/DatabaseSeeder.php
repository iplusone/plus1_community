<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Spot;
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
