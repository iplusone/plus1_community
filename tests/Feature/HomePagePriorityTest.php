<?php

namespace Tests\Feature;

use App\Models\Spot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePagePriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_chiba_priority_section(): void
    {
        Spot::factory()->create([
            'name' => '千葉県庁前案内所',
            'slug' => 'chiba-priority-spot',
            'prefecture' => '千葉県',
            'city' => '千葉市',
            'published_at' => now()->subDay(),
            'is_public' => true,
        ]);

        Spot::factory()->create([
            'name' => '渋谷案内所',
            'slug' => 'tokyo-spot',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'published_at' => now(),
            'is_public' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('千葉県の注目スポット');
        $response->assertSee('千葉県庁前案内所');
    }

    public function test_latest_section_prioritizes_chiba_before_other_prefectures(): void
    {
        Spot::factory()->create([
            'name' => '千葉優先スポット',
            'slug' => 'chiba-latest-priority',
            'prefecture' => '千葉県',
            'city' => '船橋市',
            'published_at' => now()->subDays(2),
            'is_public' => true,
        ]);

        Spot::factory()->create([
            'name' => '東京最新スポット',
            'slug' => 'tokyo-latest-spot',
            'prefecture' => '東京都',
            'city' => '新宿区',
            'published_at' => now(),
            'is_public' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeInOrder([
            '千葉優先スポット',
            '東京最新スポット',
        ]);
    }
}
